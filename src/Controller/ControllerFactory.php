<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav;
use GustavPHP\Gustav\Attribute\Middleware;
use ReflectionClass;
use ReflectionException;

class ControllerFactory
{
    /**
     * ControllerFactory constructor.
     *
     * @param class-string<Base> $class
     * @return void
     */
    public function __construct(protected string $class)
    {
    }

    /**
     * Get the controller class.
     *
     * @return class-string<Base>
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the middlewares for the controller.
     *
     * @return array<Gustav\Middleware\Base>
     * @throws ReflectionException
     * @throws ReflectionException
     */
    public function getMiddlewares(): array
    {
        $reflection = new ReflectionClass($this->class);
        $attributes = $reflection->getAttributes(Middleware::class);

        return array_map(
            fn ($attribute) => $attribute->newInstance()->getInstance(),
            $attributes,
        );
    }
}
