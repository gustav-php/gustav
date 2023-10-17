<?php

namespace GustavPHP\Gustav\Controller;

use DI\DependencyException;
use DI\NotFoundException;
use GustavPHP\Gustav;
use GustavPHP\Gustav\Attribute\Middleware;
use GustavPHP\Gustav\Service\Container;
use InvalidArgumentException;
use ReflectionClass;

class ControllerFactory
{
    protected ?object $instance = null;

    /**
     * ControllerFactory constructor.
     *
     * @param string $class
     * @return void
     */
    public function __construct(protected string $class)
    {
    }

    /**
     * Get the controller class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the controller instance.
     *
     * @return Base
     */
    public function getInstance(): Base
    {
        return $this->instance;
    }

    /**
     * Get the middlewares for the controller.
     *
     * @return array<Gustav\Middleware\Base>
     */
    public function getMiddlewares(): array
    {
        $reflection = new ReflectionClass($this->class);
        $attributes = $reflection->getAttributes(Middleware::class);

        return array_map(fn ($attribute) => $attribute->newInstance()->initialize(), $attributes);
    }

    /**
     * Initialize the controller.
     *
     * @param Container $dependencies
     * @return void
     * @throws InvalidArgumentException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function initialize(Container $dependencies): void
    {
        $this->instance = $dependencies->make($this->class);
    }
}
