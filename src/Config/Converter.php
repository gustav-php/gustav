<?php

namespace GustavPHP\Gustav\Config;

use BackedEnum;
use JsonException;
use LogicException;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionType;

/** @internal */
final readonly class Converter
{
    /**
     * @param class-string<BackedEnum>|null $enumClass
     */
    private function __construct(
        private string $name,
        private ?string $enumClass = null,
        private ?string $enumBackingType = null,
    ) {
    }

    public function convert(string $value): mixed
    {
        if ($this->enumClass !== null) {
            return $this->convertEnum($value);
        }

        return match ($this->name) {
            'array' => $this->convertArray($value),
            'bool' => $this->convertBoolean($value),
            'float' => $this->convertFloat($value),
            'int' => $this->convertInteger($value),
            'string' => $value,
            default => throw new LogicException("Unsupported compiled configuration type {$this->name}"),
        };
    }

    public static function fromReflection(?ReflectionType $type, string $location): self
    {
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("{$location} must declare one supported named type; ambiguous unions are not supported");
        }

        $name = $type->getName();
        if ($type->isBuiltin()) {
            if (!in_array($name, ['array', 'bool', 'float', 'int', 'string'], true)) {
                throw new LogicException("{$location} uses unsupported type {$name}");
            }

            return new self($name);
        }

        if (enum_exists($name) && is_subclass_of($name, BackedEnum::class)) {
            /** @var class-string<BackedEnum> $name */
            $backingType = (new ReflectionEnum($name))->getBackingType()?->getName();

            return new self($name, $name, $backingType);
        }

        throw new LogicException("{$location} uses unsupported type {$name}");
    }

    /** @return array<array-key,mixed> */
    private function convertArray(string $value): array
    {
        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ConversionFailure('invalid_array', 'Value must be valid JSON that decodes to an array');
        }

        if (!is_array($decoded)) {
            throw new ConversionFailure('invalid_array', 'Value must be valid JSON that decodes to an array');
        }

        return $decoded;
    }

    private function convertBoolean(string $value): bool
    {
        return match (strtolower($value)) {
            '1', 'true' => true,
            '0', 'false' => false,
            default => throw new ConversionFailure('invalid_boolean', 'Value must be true, false, 1, or 0'),
        };
    }

    private function convertEnum(string $value): BackedEnum
    {
        $backing = match ($this->enumBackingType) {
            'int' => $this->integerValue($value),
            'string' => $value,
            default => null,
        };

        if ($backing === null || $this->enumClass === null) {
            throw new ConversionFailure('invalid_enum', 'Value is not a valid enum case');
        }

        $case = $this->enumClass::tryFrom($backing);
        if ($case === null) {
            throw new ConversionFailure('invalid_enum', 'Value is not a valid enum case');
        }

        return $case;
    }

    private function convertFloat(string $value): float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($float === false || !is_finite($float)) {
            throw new ConversionFailure('invalid_decimal', 'Value must be decimal');
        }

        return $float;
    }

    private function convertInteger(string $value): int
    {
        $integer = $this->integerValue($value);
        if ($integer === null) {
            throw new ConversionFailure('invalid_integer', 'Value must be integer');
        }

        return $integer;
    }

    private function integerValue(string $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return $integer === false ? null : $integer;
    }
}
