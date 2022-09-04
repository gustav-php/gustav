<?php

namespace TorstenDittmann\Gustav;

use Exception;

class Router
{
    public const WILDCARD_TOKEN = '__WILDCARD__';

    protected static array $routes = [];

    protected static array $placeholders = [];

    public static function addRoute(Method $method, string $path, string $identifier): void
    {
        $path = self::preparePath($path);

        if (! array_key_exists($method->value, self::$routes)) {
            self::$routes[$method->value] = [];
        }

        if (array_key_exists($path, self::$routes[$method->value])) {
            throw new Exception("Route for ({$path}) {$identifier} already assigned to ".self::$routes[$method->value][$path]['identifier']);
        }

        self::$routes[$method->value][$path] = [
            'identifier' => $identifier,
        ];
    }

    public static function match(Method $method, string $path): array
    {
        if (! array_key_exists($method->value, self::$routes)) {
            throw new Exception('Not found');
        }

        $parts = array_values(array_filter(explode('/', $path)));
        $length = count($parts) - 1;
        $filtered_placeholders = array_filter(self::$placeholders, fn ($i) => $i <= $length);

        foreach (self::combinations($filtered_placeholders) as $sample) {
            $sample = array_filter($sample, fn ($i) => $i <= $length);
            $match = implode(
                '/',
                array_replace(
                    $parts,
                    array_fill_keys($sample, self::WILDCARD_TOKEN)
                )
            );

            if (array_key_exists($match, self::$routes[$method->value])) {
                return self::$routes[$method->value][$match];
            }
        }

        throw new Exception('Not found');
    }

    public static function reset(): void
    {
        self::$placeholders = [];
        self::$routes = [];
    }

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

    protected static function preparePath(string $path): string
    {
        $parts = array_values(array_filter(explode('/', $path)));
        $prepare = '';

        foreach ($parts as $key => $part) {
            if ($key !== 0) {
                $prepare .= '/';
            }

            if (str_starts_with($part, ':')) {
                $prepare .= self::WILDCARD_TOKEN;

                if (! in_array($key, self::$placeholders)) {
                    self::$placeholders[] = $key;
                }
            } else {
                $prepare .= $part;
            }
        }

        return $prepare;
    }
}
