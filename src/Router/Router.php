<?php

namespace GustavPHP\Gustav\Router;

use BackedEnum;
use GustavPHP\Gustav\Http\Exception\HttpException;
use InvalidArgumentException;
use Stringable;

final readonly class Router implements UrlGeneratorInterface
{
    /** @var array<string,RouteDefinition> */
    private array $namedRoutes;
    /** @var array<string,list<RouteDefinition>> */
    private array $routes;

    /**
     * @param list<RouteDefinition> $definitions
     */
    public function __construct(array $definitions)
    {
        $routes = [];
        $namedRoutes = [];

        foreach ($definitions as $definition) {
            $method = $definition->method->value;
            foreach ($routes[$method] ?? [] as $registered) {
                if ($definition->path->conflictsWith($registered->path)) {
                    throw new InvalidArgumentException(
                        "Route {$method} {$definition->path->template} for {$definition->location()} conflicts with "
                        . "{$registered->path->template} for {$registered->location()}",
                    );
                }
            }
            $routes[$method][] = $definition;

            if ($definition->name === null) {
                continue;
            }
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', $definition->name) !== 1) {
                throw new InvalidArgumentException("Route name '{$definition->name}' is invalid");
            }
            if (isset($namedRoutes[$definition->name])) {
                throw new InvalidArgumentException(
                    "Route name '{$definition->name}' is declared by both "
                    . "{$namedRoutes[$definition->name]->location()} and {$definition->location()}",
                );
            }
            $namedRoutes[$definition->name] = $definition;
        }

        foreach ($routes as &$methodRoutes) {
            usort(
                $methodRoutes,
                fn (RouteDefinition $left, RouteDefinition $right): int =>
                    $right->path->staticSegments <=> $left->path->staticSegments
                    ?: strcmp($left->path->template, $right->path->template)
                    ?: strcmp($left->location(), $right->location()),
            );
        }
        unset($methodRoutes);

        $this->routes = $routes;
        $this->namedRoutes = $namedRoutes;
    }

    /**
     * @return list<Method>
     */
    public function allowedMethods(string $path): array
    {
        $allowed = [];
        foreach (Method::cases() as $method) {
            if ($this->find($method, $path) !== null) {
                $allowed[$method->value] = $method;
                if ($method === Method::GET) {
                    $allowed[Method::HEAD->value] = Method::HEAD;
                }
            }
        }
        if ($allowed !== []) {
            $allowed[Method::OPTIONS->value] = Method::OPTIONS;
        }

        $ordered = [];
        foreach (Method::cases() as $method) {
            if (isset($allowed[$method->value])) {
                $ordered[] = $method;
            }
        }

        return $ordered;
    }

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,mixed> $query
     */
    public function generate(string $name, array $parameters = [], array $query = []): string
    {
        $route = $this->namedRoutes[$name] ?? null;
        if ($route === null) {
            throw new InvalidArgumentException("Unknown route name '{$name}'");
        }

        $path = $route->path->generate($parameters);
        if ($query === []) {
            return $path;
        }

        $encoded = http_build_query(self::normalizeQuery($query), '', '&', PHP_QUERY_RFC3986);

        return $encoded === '' ? $path : $path . '?' . $encoded;
    }

    public function hasExplicitRoute(Method $method, string $path): bool
    {
        return $this->find($method, $path) !== null;
    }

    public function match(Method $method, string $path): RouteMatch
    {
        $match = $this->find($method, $path);
        if ($match === null && $method === Method::HEAD) {
            $match = $this->find(Method::GET, $path);
        }
        if ($match !== null) {
            return $match;
        }

        $allowed = $this->allowedMethods($path);
        if ($allowed !== []) {
            throw new HttpException(
                405,
                'Method not allowed',
                ['Allow' => implode(', ', array_map(fn (Method $allowedMethod): string => $allowedMethod->value, $allowed))],
            );
        }

        throw new HttpException(404, 'Not found');
    }

    private function find(Method $method, string $path): ?RouteMatch
    {
        foreach ($this->routes[$method->value] ?? [] as $route) {
            $parameters = $route->path->match($path);
            if ($parameters !== null) {
                return new RouteMatch($route, $parameters);
            }
        }

        return null;
    }

    private static function normalizeQuery(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        if (is_array($value)) {
            return array_map(self::normalizeQuery(...), $value);
        }
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        throw new InvalidArgumentException('Query values must be scalar, stringable, backed enums, arrays, or null');
    }
}
