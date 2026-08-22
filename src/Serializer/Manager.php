<?php

namespace GustavPHP\Gustav\Serializer;

use JsonException;
use ReflectionException;

class Manager
{
    /**
     * @var array<class-string<Base>,Entity>
     */
    protected static array $entities = [];

    /** @var array<class-string<object>,ObjectMetadata> */
    protected static array $metadata = [];
    /**
     *
     * @param class-string<Base> $className
     * @return void
     * @throws ReflectionException
     */
    public static function addEntity(string $className): void
    {
        self::$entities[$className] = new Entity($className);
    }

    /**
     * Encode a value using Gustav's deterministic JSON representation.
     */
    public static function encode(mixed $value): string
    {
        try {
            return json_encode(
                self::normalize($value),
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new SerializationException('Unable to encode normalized JSON data', previous: $exception);
        }
    }

    public static function getEntity(string $className): Entity
    {
        return self::$entities[$className];
    }

    /**
     * @param class-string<object> $className
     */
    public static function metadata(string $className): ObjectMetadata
    {
        return self::$metadata[$className] ??= new ObjectMetadata($className);
    }

    public static function normalize(mixed $value): mixed
    {
        return (new Normalizer())->normalize($value);
    }

    /**
     * Compile serialization metadata before a route receives traffic.
     *
     * @param class-string<object> $className
     */
    public static function prepare(string $className): void
    {
        self::metadata($className);
    }

    /**
     * Remove all registered serializer entities.
     */
    public static function reset(): void
    {
        self::$entities = [];
        self::$metadata = [];
    }
}
