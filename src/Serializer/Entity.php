<?php

namespace GustavPHP\Gustav\Serializer;

use GustavPHP\Gustav\Attribute\Serializer\{AdditionalProperties, Exclude};
use ReflectionClass;

class Entity
{
    /**
     * @var string
     */
    protected string $className;
    /**
     * @var array<string>
     */
    protected array $excluded = [];
    /**
     * @var bool
     */
    protected bool $hasAdditionalProperties = false;
    /**
     * @var array<string>
     */
    protected array $properties = [];
    /**
     * @var ReflectionClass
     */
    protected ReflectionClass $reflection;

    /**
     * @param class-string<Base> $className
     * @return void
     */
    public function __construct(
        string $className
    ) {
        if (!\is_subclass_of($className, Base::class)) {
            throw new \InvalidArgumentException("Class {$className} is not a subclass of " . Base::class);
        }
        $this->reflection = new ReflectionClass($className);
        $this->hasAdditionalProperties = !!$this->reflection->getAttributes(AdditionalProperties::class);
        foreach ($this->reflection->getProperties() as $property) {
            $excluded = !!$property->getAttributes(Exclude::class);
            if ($excluded) {
                $this->excluded[] = $property->getName();
            } else {
                $this->properties[] = $property->getName();
            }
        }
    }

    /**
     * @param Base $instance
     * @return array<mixed>
     */
    public function serialize(Base $instance): array
    {
        $data = get_object_vars($instance);
        foreach ($this->excluded as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }
        if (!$this->hasAdditionalProperties) {
            foreach (array_keys($data) as $key) {
                if (!in_array($key, $this->properties)) {
                    unset($data[$key]);
                }
            }
        }
        foreach ($data as $key => $value) {
            if ($value instanceof Base) {
                $data[$key] = Manager::getEntity($value::class)->serialize($value);
            } elseif (is_array($value)) {
                $data[$key] = array_map(fn ($p) => Manager::getEntity($p::class)->serialize($p), array_filter($value, fn ($p) => $p instanceof Base));
            }
        }

        return $data;
    }
}
