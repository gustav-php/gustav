<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Attribute\Middleware;
use Psr\Http\Server\MiddlewareInterface;
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
     * @return array<MiddlewareInterface>
     * @throws ReflectionException
     */
    public function getMiddlewares(?string $method = null): array
    {
        $reflection = new ReflectionClass($this->class);
        $attributes = $reflection->getAttributes(Middleware::class);

        if ($method !== null) {
            $attributes = [
                ...$attributes,
                ...$reflection->getMethod($method)->getAttributes(Middleware::class),
            ];
        }

        return array_map(
            fn ($attribute) => $attribute->newInstance()->getInstance(),
            $attributes,
        );
    }
}
