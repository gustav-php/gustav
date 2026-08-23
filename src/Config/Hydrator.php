<?php

namespace GustavPHP\Gustav\Config;

use GustavPHP\Gustav\Attribute\{Config, Env, Validate};
use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use GustavPHP\Gustav\Validation\Validation;
use LogicException;
use ReflectionClass;
use ReflectionParameter;
use Throwable;

/** @internal */
final class Hydrator
{
    /** @var list<Field> */
    private array $fields = [];

    /** @var ReflectionClass<object> */
    private ReflectionClass $reflection;

    /**
     * @param class-string $className
     */
    public function __construct(private readonly string $className)
    {
        $this->reflection = new ReflectionClass($className);
        if ($this->reflection->getAttributes(Config::class) === []) {
            throw new LogicException("Configuration {$className} must declare #[Config]");
        }
        if (!$this->reflection->isReadOnly()) {
            throw new LogicException("Configuration {$className} must be readonly");
        }
        if (!$this->reflection->isInstantiable()) {
            throw new LogicException("Configuration {$className} must be instantiable");
        }

        $constructor = $this->reflection->getConstructor();
        if ($constructor === null) {
            return;
        }
        if (!$constructor->isPublic()) {
            throw new LogicException("Configuration {$className} must have a public constructor");
        }

        foreach ($constructor->getParameters() as $parameter) {
            $this->fields[] = $this->compileField($parameter);
        }
    }

    public function hydrate(Environment $environment): object
    {
        $values = [];
        $violations = [];

        foreach ($this->fields as $field) {
            $raw = $environment->get($field->variable);
            if ($raw === null) {
                if (!$field->hasDefault) {
                    $violations[] = $this->violation($field, 'required', 'Value is required');
                }

                continue;
            }

            try {
                $value = $field->converter->convert($raw);
            } catch (ConversionFailure $failure) {
                $violations[] = $this->violation($field, $failure->violationCode, $failure->getMessage());

                continue;
            }

            foreach ($field->rules as $rule) {
                $ruleViolation = $rule->getViolation($value);
                if ($ruleViolation !== null) {
                    $violations[] = $this->violation(
                        $field,
                        $ruleViolation->code,
                        $ruleViolation->message,
                    );
                }
            }

            $values[$field->property] = $value;
        }

        if ($violations !== []) {
            throw new ConfigurationException($violations);
        }

        try {
            return $this->reflection->newInstanceArgs($values);
        } catch (Throwable) {
            throw new ConfigurationException([
                new Violation(
                    configuration: $this->className,
                    property: 'constructor',
                    variable: '[constructor]',
                    code: 'constructor_failed',
                    message: 'Configuration could not be constructed',
                ),
            ]);
        }
    }

    private function compileField(ReflectionParameter $parameter): Field
    {
        $location = "Configuration constructor parameter {$this->className}::\${$parameter->getName()}";
        if ($parameter->isVariadic() || $parameter->isPassedByReference()) {
            throw new LogicException("{$location} cannot be variadic or passed by reference");
        }

        $attributes = $parameter->getAttributes(Env::class);
        if (count($attributes) !== 1) {
            throw new LogicException("{$location} must declare exactly one #[Env] attribute");
        }
        $environment = $attributes[0]->newInstance();

        return new Field(
            property: $parameter->getName(),
            variable: $environment->name,
            converter: Converter::fromReflection($parameter->getType(), $location),
            hasDefault: $parameter->isDefaultValueAvailable(),
            rules: array_map(
                function ($attribute): Validation {
                    /** @var Validate $validation */
                    $validation = $attribute->newInstance();

                    return $validation->rule;
                },
                $parameter->getAttributes(Validate::class),
            ),
        );
    }

    private function violation(Field $field, string $code, string $message): Violation
    {
        return new Violation(
            configuration: $this->className,
            property: $field->property,
            variable: $field->variable,
            code: $code,
            message: $message,
        );
    }
}
