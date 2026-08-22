<?php

namespace GustavPHP\Gustav\Serializer;

use GustavPHP\Gustav\Attribute\Serializer\{AdditionalProperties, Exclude};
use ReflectionClass;
use ReflectionProperty;
use stdClass;

final readonly class ObjectMetadata
{
    private bool $additionalProperties;

    /** @var array<string,true> */
    private array $excluded;
    /** @var list<ReflectionProperty> */
    private array $properties;

    /**
     * @param class-string<object> $className
     */
    public function __construct(private string $className)
    {
        $reflection = new ReflectionClass($className);
        if ($reflection->isInternal() && $className !== stdClass::class) {
            throw new SerializationException("Internal class {$className} does not define a supported JSON representation");
        }
        if ($reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
            throw new SerializationException("{$className} is not a serializable DTO class");
        }

        $properties = [];
        $excluded = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if ($property->getAttributes(Exclude::class) !== []) {
                $excluded[$property->getName()] = true;

                continue;
            }
            $properties[] = $property;
        }

        $this->properties = $properties;
        $this->excluded = $excluded;
        $this->additionalProperties = $className === stdClass::class
            || $reflection->getAttributes(AdditionalProperties::class) !== [];
    }

    /**
     * @param callable(mixed,string): mixed $normalize
     */
    public function normalize(object $object, callable $normalize, string $path): stdClass
    {
        $data = new stdClass();
        $declared = [];
        foreach ($this->properties as $property) {
            $name = $property->getName();
            $declared[$name] = true;
            if (!$property->isInitialized($object)) {
                throw new SerializationException("Property {$this->className}::\${$name} is not initialized at {$path}");
            }
            $data->{$name} = $normalize($property->getValue($object), self::childPath($path, $name));
        }

        if ($this->additionalProperties) {
            foreach (get_object_vars($object) as $name => $value) {
                if (isset($declared[$name]) || isset($this->excluded[$name])) {
                    continue;
                }
                $data->{$name} = $normalize($value, self::childPath($path, $name));
            }
        }

        return $data;
    }

    private static function childPath(string $path, string $name): string
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) === 1
            ? "{$path}.{$name}"
            : $path . '[' . json_encode($name, JSON_THROW_ON_ERROR) . ']';
    }
}
