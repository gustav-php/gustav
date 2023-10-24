<?php

namespace GustavPHP\Gustav\DTO;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

class Mapper
{
    /**
     * @var array<string> $required
     */
    private array $required = [];
    /**
     * @param class-string $className
     * @return void
     * @throws ReflectionException
* @throws ReflectionException
*/
    public function __construct(private readonly string $className)
    {
        $reflection = new ReflectionClass($className);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            if (!$property->hasDefaultValue()) {
                $this->required[] = $property->getName();
            }
        }
    }

    /**
     * @param array<string,mixed> $data
     * @param bool $validate
     * @return object
     * @throws Exception
     */
    public function build(array $data, bool $validate = true): object
    {
        if ($validate) {
            $this->validate($data);
        }
        $dto = new $this->className();
        foreach ($data as $property => $value) {
            $dto->{$property} = $value;
        }
        return $dto;
    }
    public static function fromReflection(ReflectionNamedType $reflection): Mapper
    {
        /**
         * @var class-string $className
         */
        $className = $reflection->getName();

        return new self($className);
    }
    public static function isParameterDTO(ReflectionParameter $reflection): bool
    {
        $type = $reflection->getType();

        return $type instanceof ReflectionNamedType && class_exists($type->getName());
    }
    /**
     *
     * @param array<string,mixed> $data
     * @return bool
     * @throws Exception
     */
    public function validate(array $data): bool
    {
        foreach ($this->required as $property) {
            if (!array_key_exists($property, $data)) {
                throw new Exception("Missing required property: {$property}");
            }
        }
        return true;
    }
}
