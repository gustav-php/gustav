<?php

namespace TorstenDittmann\Gustav\Controller;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use TorstenDittmann\Gustav\Attribute\Middleware;
use TorstenDittmann\Gustav\Context;
use TorstenDittmann\Gustav\Service;

class ControllerFactory
{
    protected ?Context $context = null;
    protected ?object $instance = null;
    protected array $injections = [];
    protected array $middlewares = [];
    public function __construct(protected string $class)
    {
    }

    public function getInjections(): array
    {
        return $this->injections;
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

    public function setMiddlewares(): self
    {
        $reflection = new ReflectionClass($this->class);
        $attributes = $reflection->getAttributes(Middleware::class);

        foreach ($attributes as $attribute) {
            $this->middlewares[] = $attribute->newInstance()->initialize();
        }

        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function initialize(...$args)
    {
        $this->instance = new $this->class(...$args);
    }
}
