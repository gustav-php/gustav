<?php

namespace GustavPHP\Gustav\DTO;

use GustavPHP\Gustav\Attribute\Validate;
use GustavPHP\Gustav\Input\{ConversionResult, TypeConverter};
use GustavPHP\Gustav\Validation\{Validation, Violation};
use LogicException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;

final class Hydrator
{
    /** @var list<DtoField> */
    private array $fields = [];
    private ReflectionClass $reflection;
    private bool $usesConstructor;

    /**
     * @param class-string<object> $className
     */
    public function __construct(private readonly string $className)
    {
        $this->reflection = new ReflectionClass($className);
        if (!$this->reflection->isInstantiable()) {
            throw new LogicException("DTO {$className} must be instantiable");
        }

        $constructor = $this->reflection->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
            $this->usesConstructor = true;
            if (!$constructor->isPublic()) {
                throw new LogicException("DTO {$className} must have a public constructor");
            }

            foreach ($constructor->getParameters() as $parameter) {
                $this->fields[] = $this->constructorField($parameter);
            }

            return;
        }

        $this->usesConstructor = false;
        if ($constructor !== null && !$constructor->isPublic()) {
            throw new LogicException("DTO {$className} must have a public constructor");
        }

        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if ($property->isReadOnly()) {
                throw new LogicException(
                    "Readonly DTO property {$className}::\${$property->getName()} must be constructor-promoted",
                );
            }

            $this->fields[] = new DtoField(
                $property->getName(),
                TypeConverter::fromReflection(
                    $property->getType(),
                    "DTO property {$className}::\${$property->getName()}",
                ),
                $property->hasDefaultValue(),
                $this->rules($property),
                $property,
            );
        }
    }

    /**
     * @param array<array-key,mixed> $data
     */
    public function hydrate(array $data, string $source, string $path = ''): ConversionResult
    {
        $violations = [];
        $values = [];
        $knownFields = array_fill_keys(array_map(fn (DtoField $field): string => $field->name, $this->fields), true);

        foreach ($data as $name => $_value) {
            if (!is_string($name) || !array_key_exists($name, $knownFields)) {
                $violations[] = new Violation(
                    $source,
                    $this->joinPath($path, (string) $name),
                    'unknown_field',
                    'Field is not allowed',
                );
            }
        }

        foreach ($this->fields as $field) {
            $fieldPath = $this->joinPath($path, $field->name);
            if (!array_key_exists($field->name, $data)) {
                if (!$field->hasDefault) {
                    $violations[] = new Violation($source, $fieldPath, 'required', 'Value is required');
                }

                continue;
            }

            $result = $field->converter->convert($data[$field->name], $source, $fieldPath);
            if (!$result->isValid) {
                array_push($violations, ...$result->violations);

                continue;
            }

            foreach ($field->rules as $rule) {
                $ruleViolation = $rule->getViolation($result->value);
                if ($ruleViolation !== null) {
                    $violations[] = new Violation(
                        $source,
                        $fieldPath,
                        $ruleViolation->code,
                        $ruleViolation->message,
                    );
                }
            }

            $values[$field->name] = $result->value;
        }

        if ($violations !== []) {
            return ConversionResult::failures($violations);
        }

        if ($this->usesConstructor) {
            return ConversionResult::success($this->reflection->newInstanceArgs($values));
        }

        $object = $this->reflection->newInstance();
        foreach ($this->fields as $field) {
            if (array_key_exists($field->name, $values)) {
                $field->property?->setValue($object, $values[$field->name]);
            }
        }

        return ConversionResult::success($object);
    }

    private function constructorField(ReflectionParameter $parameter): DtoField
    {
        if ($parameter->isVariadic() || $parameter->isPassedByReference()) {
            throw new LogicException(
                "DTO constructor parameter {$this->className}::\${$parameter->getName()} cannot be variadic or passed by reference",
            );
        }

        return new DtoField(
            $parameter->getName(),
            TypeConverter::fromReflection(
                $parameter->getType(),
                "DTO constructor parameter {$this->className}::\${$parameter->getName()}",
            ),
            $parameter->isDefaultValueAvailable(),
            $this->rules($parameter),
        );
    }

    private function joinPath(string $prefix, string $field): string
    {
        return $prefix === '' ? $field : "{$prefix}.{$field}";
    }

    /**
     * @return list<Validation>
     */
    private function rules(ReflectionParameter|ReflectionProperty $reflection): array
    {
        return array_map(
            function ($attribute): Validation {
                /** @var Validate $validation */
                $validation = $attribute->newInstance();

                return $validation->rule;
            },
            $reflection->getAttributes(Validate::class),
        );
    }
}
