<?php

namespace GustavPHP\Gustav\Serializer;

use InvalidArgumentException;
use stdClass;

class Entity
{
    /**
     * @param class-string<Base> $className
     */
    public function __construct(
        protected string $className
    ) {
        if (!is_subclass_of($className, Base::class)) {
            throw new InvalidArgumentException("Class {$className} is not a subclass of " . Base::class);
        }
        Manager::prepare($className);
    }

    /**
     * @param Base $instance
     * @return array<mixed>
     */
    public function serialize(Base $instance): array
    {
        if (!$instance instanceof $this->className) {
            throw new InvalidArgumentException("Serializer instance must be an instance of {$this->className}");
        }

        $normalized = Manager::normalize($instance);
        if (!$normalized instanceof stdClass) {
            throw new SerializationException("Serializer {$this->className} did not normalize to an object");
        }

        return self::objectToArray($normalized);
    }

    private static function normalizedToArray(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            return self::objectToArray($value);
        }
        if (is_array($value)) {
            return array_map(self::normalizedToArray(...), $value);
        }

        return $value;
    }

    /**
     * @return array<mixed>
     */
    private static function objectToArray(stdClass $object): array
    {
        $data = [];
        foreach (get_object_vars($object) as $key => $value) {
            $data[$key] = self::normalizedToArray($value);
        }

        return $data;
    }
}
