<?php

namespace GustavPHP\Gustav\Controller;

use Exception;
use GustavPHP\Gustav\Attribute\Middleware;
use GustavPHP\Gustav\Service;
use GustavPHP\Gustav\Service\Container;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class ControllerFactory
{
    protected array $injections = [];
    protected ?object $instance = null;
    public function __construct(protected string $class)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function getMiddlewares(): array
    {
        $reflection = new ReflectionClass($this->class);
        $attributes = $reflection->getAttributes(Middleware::class);

        return array_map(fn ($attribute) => $attribute->newInstance()->initialize(), $attributes);
    }

    public function initialize(Container $dependencies)
    {
        $this->instance = $dependencies->make($this->class);
    }

    public function setInjections(ReflectionMethod $constructor): self
    {
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType) {
                throw new Exception("Parameter {$parameter->getName()} has no valid type.");
            }

            $name = $type->getName();
            if (!is_subclass_of($name, Service\Base::class)) {
                throw new Exception("Service {$name} is not a subclass of " . Service\Base::class);
            }

            $this->injections[$parameter->getPosition()] = $name;
        }

        return $this;
    }
}
