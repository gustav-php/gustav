<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Attribute\Middleware;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;

class ControllerFactory
{
    /** @var array<string, array<class-string<MiddlewareInterface>>> */
    private array $methodMiddlewares = [];
    /** @var array<class-string<MiddlewareInterface>> */
    private array $middlewares;

    /**
     * ControllerFactory constructor.
     *
     * @param class-string<Base> $class
     * @return void
     */
    public function __construct(
        protected string $class,
        ?ReflectionClass $reflection = null,
    ) {
        $reflection ??= new ReflectionClass($class);
        $this->middlewares = $this->compileMiddlewares($reflection->getAttributes(Middleware::class));

        foreach ($reflection->getMethods() as $method) {
            $this->methodMiddlewares[$method->getName()] = $this->compileMiddlewares(
                $method->getAttributes(Middleware::class),
            );
        }
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
     * Get the compiled middleware classes for the controller and method.
     *
     * @return array<class-string<MiddlewareInterface>>
     */
    public function getMiddlewareClasses(?string $method = null): array
    {
        return [
            ...$this->middlewares,
            ...($method === null ? [] : ($this->methodMiddlewares[$method] ?? [])),
        ];
    }

    /**
     * @param array<\ReflectionAttribute<Middleware>> $attributes
     * @return array<class-string<MiddlewareInterface>>
     */
    private function compileMiddlewares(array $attributes): array
    {
        return array_map(
            fn ($attribute): string => $attribute->newInstance()->getClass(),
            $attributes,
        );
    }
}
