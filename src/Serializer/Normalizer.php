<?php

namespace GustavPHP\Gustav\Serializer;

use BackedEnum;
use Closure;
use JsonSerializable;
use SplObjectStorage;
use UnitEnum;

final class Normalizer
{
    private const MAX_DEPTH = 64;

    /** @var SplObjectStorage<object,null> */
    private SplObjectStorage $activeObjects;

    public function __construct()
    {
        $this->activeObjects = new SplObjectStorage();
    }

    public function normalize(mixed $value, string $path = '$', int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            throw new SerializationException("Maximum JSON serialization depth exceeded at {$path}");
        }
        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return $value;
        }
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new SerializationException("Non-finite float cannot be serialized at {$path}");
            }

            return $value;
        }
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item, self::arrayPath($path, $key), $depth + 1);
            }

            return $normalized;
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof UnitEnum) {
            throw new SerializationException('Unbacked enum ' . $value::class . " cannot be serialized at {$path}");
        }
        if ($value instanceof Closure || is_resource($value)) {
            throw new SerializationException('Unsupported value of type ' . get_debug_type($value) . " at {$path}");
        }
        if (!is_object($value)) {
            throw new SerializationException('Unsupported value of type ' . get_debug_type($value) . " at {$path}");
        }
        if ($this->activeObjects->offsetExists($value)) {
            throw new SerializationException("Circular object reference detected at {$path}");
        }

        $this->activeObjects->offsetSet($value);
        try {
            if ($value instanceof JsonSerializable) {
                return $this->normalize($value->jsonSerialize(), $path, $depth + 1);
            }

            return Manager::metadata($value::class)->normalize(
                $value,
                fn (mixed $item, string $itemPath): mixed => $this->normalize($item, $itemPath, $depth + 1),
                $path,
            );
        } finally {
            $this->activeObjects->offsetUnset($value);
        }
    }

    private static function arrayPath(string $path, int|string $key): string
    {
        if (is_int($key)) {
            return "{$path}[{$key}]";
        }

        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) === 1
            ? "{$path}.{$key}"
            : $path . '[' . json_encode($key, JSON_THROW_ON_ERROR) . ']';
    }
}
