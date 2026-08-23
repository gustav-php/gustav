<?php

namespace GustavPHP\Gustav\Input;

use BackedEnum;
use GustavPHP\Gustav\DTO\Hydrator;
use GustavPHP\Gustav\Validation\Violation;
use LogicException;
use ReflectionNamedType;
use ReflectionType;

final readonly class TypeConverter
{
    /**
     * @param class-string<BackedEnum>|null $enumClass
     */
    private function __construct(
        private string $name,
        private bool $nullable,
        private ?string $enumClass = null,
        private ?string $enumBackingType = null,
        private ?Hydrator $hydrator = null,
    ) {
    }

    public function acceptsWholeInput(): bool
    {
        return $this->name === 'array' || $this->hydrator !== null;
    }

    public function convert(mixed $value, string $source, string $path): ConversionResult
    {
        if ($value === null) {
            return $this->nullable
                ? ConversionResult::success(null)
                : $this->invalid($source, $path, 'not_nullable', 'Value cannot be null');
        }

        if ($this->hydrator !== null) {
            return is_array($value)
                ? $this->hydrator->hydrate($value, $source, $path)
                : $this->invalid($source, $path, 'invalid_object', 'Value must be an object');
        }

        if ($this->enumClass !== null) {
            return $this->convertEnum($value, $source, $path);
        }

        return match ($this->name) {
            'array' => is_array($value)
                ? ConversionResult::success($value)
                : $this->invalid($source, $path, 'invalid_array', 'Value must be an array'),
            'bool' => $this->convertBoolean($value, $source, $path),
            'float' => $this->convertFloat($value, $source, $path),
            'int' => $this->convertInteger($value, $source, $path),
            'string' => is_string($value)
                ? ConversionResult::success($value)
                : $this->invalid($source, $path, 'invalid_string', 'Value must be a string'),
            default => throw new LogicException("Unsupported compiled type {$this->name}"),
        };
    }

    public static function fromReflection(
        ?ReflectionType $type,
        string $location,
        bool $allowDto = false,
    ): self {
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("{$location} must declare one supported named type; ambiguous unions are not supported");
        }

        $name = $type->getName();
        if ($type->isBuiltin()) {
            if (!in_array($name, ['array', 'bool', 'float', 'int', 'string'], true)) {
                throw new LogicException("{$location} uses unsupported type {$name}");
            }

            return new self($name, $type->allowsNull());
        }

        if (enum_exists($name) && is_subclass_of($name, BackedEnum::class)) {
            /** @var class-string<BackedEnum> $name */
            $backingType = (new \ReflectionEnum($name))->getBackingType()?->getName();

            return new self($name, $type->allowsNull(), $name, $backingType);
        }

        if (!$allowDto || !class_exists($name)) {
            throw new LogicException("{$location} uses unsupported input type {$name}");
        }

        /** @var class-string<object> $name */
        return new self($name, $type->allowsNull(), hydrator: new Hydrator($name));
    }

    public function isArray(): bool
    {
        return $this->name === 'array';
    }

    public function isBoolean(): bool
    {
        return $this->name === 'bool';
    }

    private function convertBoolean(mixed $value, string $source, string $path): ConversionResult
    {
        if (is_bool($value)) {
            return ConversionResult::success($value);
        }
        if ($value === 1 || $value === '1') {
            return ConversionResult::success(true);
        }
        if ($value === 0 || $value === '0') {
            return ConversionResult::success(false);
        }
        if (is_string($value)) {
            return match (strtolower($value)) {
                'true' => ConversionResult::success(true),
                'false' => ConversionResult::success(false),
                default => $this->invalid($source, $path, 'invalid_boolean', 'Value must be boolean'),
            };
        }

        return $this->invalid($source, $path, 'invalid_boolean', 'Value must be boolean');
    }

    private function convertEnum(mixed $value, string $source, string $path): ConversionResult
    {
        $backing = match ($this->enumBackingType) {
            'int' => $this->integerValue($value),
            'string' => is_string($value) ? $value : null,
            default => null,
        };

        if ($backing === null || $this->enumClass === null) {
            return $this->invalid($source, $path, 'invalid_enum', 'Value is not a valid enum case');
        }

        $case = $this->enumClass::tryFrom($backing);

        return $case === null
            ? $this->invalid($source, $path, 'invalid_enum', 'Value is not a valid enum case')
            : ConversionResult::success($case);
    }

    private function convertFloat(mixed $value, string $source, string $path): ConversionResult
    {
        if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
            return ConversionResult::success((float) $value);
        }

        if (is_string($value)) {
            $float = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($float !== false && is_finite($float)) {
                return ConversionResult::success($float);
            }
        }

        return $this->invalid($source, $path, 'invalid_decimal', 'Value must be decimal');
    }

    private function convertInteger(mixed $value, string $source, string $path): ConversionResult
    {
        $integer = $this->integerValue($value);

        return $integer === null
            ? $this->invalid($source, $path, 'invalid_integer', 'Value must be integer')
            : ConversionResult::success($integer);
    }

    private function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (!is_string($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return $integer === false ? null : $integer;
    }

    private function invalid(
        string $source,
        string $path,
        string $code,
        string $message,
    ): ConversionResult {
        return ConversionResult::failure(new Violation($source, $path, $code, $message));
    }
}
