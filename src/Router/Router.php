<?php

namespace GustavPHP\Gustav\Router;

use Exception;
use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\Response;

class Router
{
    /**
     * Placeholder token.
     */
    public const PLACEHOLDER_TOKEN = ':::';

    /**
     * Contains the positions of all params in the paths of all registered Routes.
     *
     * @var array<int>
     */
    protected static array $params = [];

    /**
     * Contains all registered Routes.
     *
     * @var array<string,Route[]>
     */
    protected static array $routes = [];

    /**
     * Add a Route to the Router.
     *
     * @param Route $route
     * @return void
     * @throws Exception
     */
    public static function addRoute(Route $route): void
    {
        [$path, $placeholders] = self::preparePath($route->getPath());

        if (!array_key_exists($route->getMethod()->value, self::$routes)) {
            self::$routes[$route->getMethod()->value] = [];
        }

        if (array_key_exists($path, self::$routes[$route->getMethod()->value])) {
            throw new Exception("Route for ({$path}) " . $route->getClass() . ' already assigned to ' . self::$routes[$route->getMethod()->value][$path]->getClass());
        }

        foreach ($placeholders as $key => $index) {
            $route->addPlaceholder($key, $index);
        }

        self::$routes[$route->getMethod()->value][$path] = $route;
    }

    /**
     * Match a Route to the given Method and Path.
     *
     * @param Method $method
     * @param string $path
     * @return Route
     * @throws Exception
     */
    public static function match(Method $method, string $path): Route
    {
        if (!array_key_exists($method->value, self::$routes)) {
            throw new Exception('Not found', Response::STATUS_NOT_FOUND);
        }

        $parts = array_values(array_filter(explode('/', $path)));
        $length = count($parts) - 1;
        $filteredParams = array_filter(self::$params, fn ($i) => $i <= $length);

        foreach (self::combinations($filteredParams) as $sample) {
            $sample = array_filter($sample, fn ($i) => $i <= $length);
            $match = implode(
                '/',
                array_replace(
                    $parts,
                    array_fill_keys($sample, self::PLACEHOLDER_TOKEN)
                )
            );

            if (array_key_exists($match, self::$routes[$method->value])) {
                return self::$routes[$method->value][$match];
            }
        }

        throw new Exception('Not found', Response::STATUS_NOT_FOUND);
    }

    /**
     * Reset the Router.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$params = [];
        self::$routes = [];
    }

    /**
     * Generate all possible combinations of the given set.
     *
     * @param array $set
     * @return iterable
     */
    protected static function combinations(array $set): iterable
    {
        yield [];

        $results = [[]];

        foreach ($set as $element) {
            foreach ($results as $combination) {
                $ret = array_merge([$element], $combination);
                $results[] = $ret;

                yield $ret;
            }
        }
    }

    /**
     * Prepare the given path with placeholder tokens.
     * .
     * @param string $path
     * @return array
     */
    protected static function preparePath(string $path): array
    {
        $parts = array_values(array_filter(explode('/', $path)));
        $prepare = '';
        $params = [];

        foreach ($parts as $key => $part) {
            if ($key !== 0) {
                $prepare .= '/';
            }

            if (str_starts_with($part, ':')) {
                $prepare .= self::PLACEHOLDER_TOKEN;
                $params[ltrim($part, ':')] = $key;
                if (!in_array($key, self::$params)) {
                    self::$params[] = $key;
                }
            } else {
                $prepare .= $part;
            }
        }

        return [$prepare, $params];
    }
}
