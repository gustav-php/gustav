<?php

namespace TorstenDittmann\Gustav;

use Exception;

class Router
{
    public const WILDCARD_TOKEN = '__WILDCARD__';
    static protected array $routes = [];
    static protected array $placeholders = [];

    static public function addRoute(string $path, string $identifier): void
    {
        $path = self::preparePath($path);

        if (array_key_exists($path, self::$routes)) {
            throw new Exception("Route for ({$path}) {$identifier} already assigned to " . self::$routes[$path]['identifier']);
        }

        self::$routes[$path] = [
            'identifier' => $identifier
        ];
    }

    static public function match(string $path): array
    {
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

            if (array_key_exists($match, self::$routes)) {
                return self::$routes[$match];
            }
        }

        throw new Exception('Not found');
    }

    static protected function combinations(array $set): iterable
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

        return;
    }

    static protected function preparePath(string $path): string
    {
        $parts = array_values(array_filter(explode('/', $path)));
        $prepare = '';

        foreach ($parts as $key => $part) {
            if ($key !== 0) {
                $prepare .= '/';
            }

            if (str_starts_with($part, ':')) {
                $prepare .= self::WILDCARD_TOKEN;

                if (!in_array($key, self::$placeholders)) {
                    self::$placeholders[] = $key;
                }
            } else {
                $prepare .= $part;
            }
        }

        return $prepare;
    }
}
