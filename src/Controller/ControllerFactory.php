<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Attribute\Middleware;
use GustavPHP\Gustav\Service\Container;
use ReflectionClass;

class ControllerFactory
{
    protected ?object $instance = null;

    public function __construct(protected string $class)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getInstance(): Base
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
}
