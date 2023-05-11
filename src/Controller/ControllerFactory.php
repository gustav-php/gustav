<?php

namespace GustavPHP\Gustav\Controller;

use Exception;
use GustavPHP\Gustav\Attribute\Middleware;
use GustavPHP\Gustav\Middleware\Lifecycle;
use GustavPHP\Gustav\Service;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class ControllerFactory
{
    protected array $after = [];
    protected array $before = [];
    protected array $error = [];
    protected array $injections = [];
    protected ?object $instance = null;
    public function __construct(protected string $class)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getInjections(): array
    {
        return $this->injections;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function getMiddlewares(Lifecycle $lifecycle): array
    {
        return match($lifecycle) {
            Lifecycle::After => $this->after,
            Lifecycle::Before => $this->before,
            Lifecycle::Error => $this->error
        };
    }

    public function initialize(...$args)
    {
        $this->instance = new $this->class(...$args);
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
            /**
             * @var Middleware $instance
             */
            $instance = $attribute->newInstance();
            switch ($instance->getLifecycle()) {
                case Lifecycle::After:
                    $this->after[] = $instance->initialize();
                    break;
                case Lifecycle::Before:
                    $this->before[] = $instance->initialize();
                    break;
                case Lifecycle::Error:
                    $this->error[] = $instance->initialize();
                    break;
            }
        }

        return $this;
    }
}
