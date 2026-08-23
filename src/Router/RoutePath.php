<?php

namespace GustavPHP\Gustav\Router;

use BackedEnum;
use InvalidArgumentException;
use Stringable;

/** @internal */
final readonly class RoutePath
{
    /**
     * @param list<array{literal:?string,parameter:?string}> $segments
     * @param list<string> $parameters
     */
    private function __construct(
        public string $template,
        public int $staticSegments,
        private array $segments,
        private array $parameters,
    ) {
    }

    public static function compile(string $path): self
    {
        $path = trim($path);
        if (str_contains($path, '?') || str_contains($path, '#')) {
            throw new InvalidArgumentException("Route path '{$path}' cannot contain a query string or fragment");
        }
        if (str_contains(trim($path, '/'), '//')) {
            throw new InvalidArgumentException("Route path '{$path}' cannot contain empty segments");
        }

        $parts = self::split($path);
        $segments = [];
        $parameters = [];
        $template = [];
        $staticSegments = 0;

        foreach ($parts as $part) {
            if (preg_match('/^\{([^{}]+)\}$/', $part, $matches) === 1) {
                $parameter = $matches[1];
                if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $parameter) !== 1) {
                    throw new InvalidArgumentException(
                        "Route parameter '{$parameter}' must start with a letter or underscore and contain only letters, numbers, and underscores",
                    );
                }
                if (in_array($parameter, $parameters, true)) {
                    throw new InvalidArgumentException("Route path '{$path}' declares parameter '{$parameter}' more than once");
                }

                $segments[] = ['literal' => null, 'parameter' => $parameter];
                $parameters[] = $parameter;
                $template[] = '{' . $parameter . '}';

                continue;
            }
            if (str_contains($part, '{') || str_contains($part, '}')) {
                throw new InvalidArgumentException("Route path '{$path}' contains an invalid parameter segment '{$part}'");
            }

            $segments[] = ['literal' => rawurldecode($part), 'parameter' => null];
            $template[] = $part;
            $staticSegments++;
        }

        return new self(
            self::assemble($template),
            $staticSegments,
            $segments,
            $parameters,
        );
    }

    public function conflictsWith(self $other): bool
    {
        if (
            count($this->segments) !== count($other->segments)
            || $this->staticSegments !== $other->staticSegments
        ) {
            return false;
        }

        foreach ($this->segments as $index => $segment) {
            $otherSegment = $other->segments[$index];
            if (
                $segment['literal'] !== null
                && $otherSegment['literal'] !== null
                && $segment['literal'] !== $otherSegment['literal']
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function generate(array $parameters): string
    {
        $missing = array_values(array_diff($this->parameters, array_keys($parameters)));
        $unknown = array_values(array_diff(array_keys($parameters), $this->parameters));
        if ($missing !== []) {
            throw new InvalidArgumentException('Missing route parameters: ' . implode(', ', $missing));
        }
        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown route parameters: ' . implode(', ', $unknown));
        }

        $parts = [];
        foreach ($this->segments as $segment) {
            if ($segment['literal'] !== null) {
                $parts[] = rawurlencode($segment['literal']);

                continue;
            }

            $name = $segment['parameter'];
            if ($name === null) {
                throw new InvalidArgumentException('Compiled route segment is invalid');
            }
            $parts[] = rawurlencode(self::stringify($parameters[$name]));
        }

        return self::assemble($parts);
    }

    public static function join(string $prefix, string $path): string
    {
        $parts = [];
        foreach ([$prefix, $path] as $part) {
            $part = trim(trim($part), '/');
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return self::assemble($parts);
    }

    /**
     * @return null|array<string,string>
     */
    public function match(string $path): ?array
    {
        $parts = self::split($path);
        if (count($parts) !== count($this->segments)) {
            return null;
        }

        $parameters = [];
        foreach ($this->segments as $index => $segment) {
            $value = rawurldecode($parts[$index]);
            if ($segment['literal'] !== null) {
                if ($segment['literal'] !== $value) {
                    return null;
                }

                continue;
            }

            $name = $segment['parameter'];
            if ($name !== null) {
                $parameters[$name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * @param list<string> $parts
     */
    private static function assemble(array $parts): string
    {
        return $parts === [] ? '/' : '/' . implode('/', $parts);
    }

    /**
     * @return list<string>
     */
    private static function split(string $path): array
    {
        $path = trim(trim($path), '/');
        if ($path === '') {
            return [];
        }

        return explode('/', $path);
    }

    private static function stringify(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Route parameter values must be scalar, stringable, or backed enums');
    }
}
