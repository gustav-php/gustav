<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Attribute\{Argument, Command, Option, Validate};
use GustavPHP\Gustav\Input\TypeConverter;
use GustavPHP\Gustav\Validation\Validation;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/** @internal */
final readonly class CommandDefinition
{
    private const RESERVED_OPTION_NAMES = [
        'ansi',
        'help',
        'no-ansi',
        'no-interaction',
        'quiet',
        'verbose',
        'version',
    ];

    private const RESERVED_OPTION_SHORTCUTS = ['h', 'n', 'q', 'v', 'V'];

    /**
     * @param class-string $class
     * @param list<ParameterMetadata> $parameters
     */
    private function __construct(
        public string $class,
        public string $name,
        public string $description,
        public bool $hidden,
        public ReflectionMethod $method,
        public array $parameters,
    ) {
    }

    /**
     * @param class-string $class
     */
    public static function compile(string $class): self
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new LogicException("Command {$class} must be instantiable");
        }

        $attributes = $reflection->getAttributes(Command::class);
        if (count($attributes) !== 1) {
            throw new LogicException("Command {$class} must declare exactly one " . Command::class . ' attribute');
        }
        $command = $attributes[0]->newInstance();

        if (!$reflection->hasMethod('__invoke')) {
            throw new LogicException("Command {$class} must declare a public __invoke() method");
        }
        $method = $reflection->getMethod('__invoke');
        if (!$method->isPublic() || $method->isStatic()) {
            throw new LogicException("Command {$class}::__invoke() must be public and non-static");
        }
        self::assertReturnType($method, $class);

        $parameters = [];
        $names = [];
        $shortcuts = [];
        $optionalArgumentSeen = false;
        $arrayArgumentSeen = false;

        foreach ($method->getParameters() as $parameter) {
            $metadata = self::compileParameter($method, $parameter);
            $key = $metadata->kind->value . ':' . $metadata->inputName;
            if (isset($names[$key])) {
                throw new LogicException(
                    "Command {$class} declares duplicate {$metadata->kind->value} {$metadata->inputName}",
                );
            }
            $names[$key] = true;

            if ($metadata->shortcut !== null) {
                if (isset($shortcuts[$metadata->shortcut])) {
                    throw new LogicException(
                        "Command {$class} declares duplicate option shortcut {$metadata->shortcut}",
                    );
                }
                $shortcuts[$metadata->shortcut] = true;
            }

            if ($metadata->kind === InputKind::Argument) {
                if ($arrayArgumentSeen) {
                    throw new LogicException("Array argument {$metadata->inputName} must be the final command argument");
                }
                if ($optionalArgumentSeen && !$metadata->hasDefault) {
                    throw new LogicException(
                        "Required argument {$metadata->inputName} cannot follow an optional command argument",
                    );
                }
                $optionalArgumentSeen = $optionalArgumentSeen || $metadata->hasDefault;
                $arrayArgumentSeen = $metadata->converter->isArray();
            }

            $parameters[] = $metadata;
        }

        return new self(
            $class,
            $command->name,
            $command->description,
            $command->hidden,
            $method,
            $parameters,
        );
    }

    private static function assertReturnType(ReflectionMethod $method, string $class): void
    {
        $type = $method->getReturnType();
        if (
            !$type instanceof ReflectionNamedType
            || !$type->isBuiltin()
            || !in_array($type->getName(), ['int', 'void'], true)
        ) {
            throw new LogicException("Command {$class}::__invoke() must declare an int or void return type");
        }
    }

    private static function compileParameter(
        ReflectionMethod $method,
        ReflectionParameter $parameter,
    ): ParameterMetadata {
        if ($parameter->isVariadic() || $parameter->isPassedByReference()) {
            throw new LogicException(
                "Command parameter {$method->getDeclaringClass()->getName()}::__invoke(\${$parameter->getName()}) cannot be variadic or passed by reference",
            );
        }

        $attributes = [
            ...$parameter->getAttributes(Argument::class),
            ...$parameter->getAttributes(Option::class),
        ];
        $location = "Command parameter {$method->getDeclaringClass()->getName()}::__invoke(\${$parameter->getName()})";
        if (count($attributes) !== 1) {
            throw new LogicException("{$location} must declare exactly one command input attribute");
        }

        $attribute = $attributes[0]->newInstance();
        $kind = $attribute instanceof Argument ? InputKind::Argument : InputKind::Option;
        $name = $attribute->name ?? self::kebabCase($parameter->getName());
        $shortcut = $attribute instanceof Option ? $attribute->shortcut : null;

        $metadata = new ParameterMetadata(
            $parameter->getName(),
            $name,
            $kind,
            $attribute->description,
            $shortcut,
            TypeConverter::fromReflection($parameter->getType(), $location),
            $parameter->isDefaultValueAvailable(),
            $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            self::rules($parameter),
        );

        if ($metadata->kind === InputKind::Option) {
            if (in_array($metadata->inputName, self::RESERVED_OPTION_NAMES, true)) {
                throw new LogicException("{$location} uses reserved option --{$metadata->inputName}");
            }
            if (
                $metadata->shortcut !== null
                && in_array($metadata->shortcut, self::RESERVED_OPTION_SHORTCUTS, true)
            ) {
                throw new LogicException("{$location} uses reserved option shortcut -{$metadata->shortcut}");
            }
            if ($metadata->converter->isBoolean() && !$metadata->hasDefault) {
                throw new LogicException("{$location} must declare a PHP default for a boolean option");
            }
        }

        return $metadata;
    }

    private static function kebabCase(string $name): string
    {
        $converted = preg_replace('/(?<!^)[A-Z]/', '-$0', $name);

        return strtolower($converted ?? $name);
    }

    /**
     * @return list<Validation>
     */
    private static function rules(ReflectionParameter $parameter): array
    {
        return array_map(
            function (ReflectionAttribute $attribute): Validation {
                /** @var Validate $validation */
                $validation = $attribute->newInstance();

                return $validation->rule;
            },
            $parameter->getAttributes(Validate::class),
        );
    }
}
