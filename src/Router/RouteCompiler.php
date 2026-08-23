<?php

namespace GustavPHP\Gustav\Router;

use GustavPHP\Gustav\Attribute\{Controller, Middleware, Route};
use GustavPHP\Gustav\Http\Binding\RequestBinder;
use GustavPHP\Gustav\Http\ResponseHandler;
use LogicException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/** @internal */
final class RouteCompiler
{
    /**
     * @param class-string $class
     * @return list<RouteDefinition>
     */
    public static function compile(string $class): array
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new LogicException("Controller '{$class}' must be instantiable");
        }

        $controllerAttributes = $reflection->getAttributes(Controller::class);
        if (count($controllerAttributes) !== 1) {
            throw new LogicException("Controller '{$class}' must declare exactly one #[Controller] attribute");
        }
        $controller = $controllerAttributes[0]->newInstance();
        $controllerMiddlewares = self::middlewares($reflection->getAttributes(Middleware::class));
        $definitions = [];

        foreach ($reflection->getMethods() as $method) {
            $routes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($routes === []) {
                continue;
            }
            self::assertHandler($class, $method);
            $middlewares = [
                ...$controllerMiddlewares,
                ...self::middlewares($method->getAttributes(Middleware::class)),
            ];

            foreach ($routes as $routeAttribute) {
                $route = $routeAttribute->newInstance();
                $path = RoutePath::compile(RoutePath::join($controller->path, $route->getPath()));
                $definitions[] = new RouteDefinition(
                    path: $path,
                    method: $route->getMethod(),
                    name: $route->getName(),
                    controller: $class,
                    handler: $method->getName(),
                    requestBinder: RequestBinder::compile($method, $path->template),
                    responseHandler: ResponseHandler::compile($method),
                    middlewares: $middlewares,
                );
            }
        }

        if ($definitions === []) {
            throw new LogicException("Controller '{$class}' must declare at least one route handler");
        }

        return $definitions;
    }

    /** @param class-string $class */
    private static function assertHandler(string $class, ReflectionMethod $method): void
    {
        $location = "Controller method {$class}::{$method->getName()}()";
        if (!$method->isPublic()) {
            throw new LogicException("{$location} must be public");
        }
        if ($method->isStatic()) {
            throw new LogicException("{$location} cannot be static");
        }
    }

    /**
     * @param array<ReflectionAttribute<Middleware>> $attributes
     * @return list<class-string<MiddlewareInterface>>
     */
    private static function middlewares(array $attributes): array
    {
        $middlewares = [];
        foreach ($attributes as $attribute) {
            $middlewares[] = $attribute->newInstance()->getClass();
        }

        return $middlewares;
    }
}
