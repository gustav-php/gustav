<?php

namespace GustavPHP\Gustav\Service;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/** @internal */
final readonly class Factory
{
    private function __construct(
        private Closure $factory,
        private bool $receivesContainer,
    ) {
    }

    public static function compile(string $id, callable $factory): self
    {
        $closure = Closure::fromCallable($factory);
        $reflection = new ReflectionFunction($closure);
        $parameters = $reflection->getParameters();

        if (count($parameters) > 1) {
            throw new InvalidArgumentException(
                "Definition for '{$id}' must accept 0 or 1 parameter, " . count($parameters) . ' given',
            );
        }

        $parameter = $parameters[0] ?? null;
        if ($parameter !== null && !self::acceptsContainer($parameter->getType())) {
            throw new InvalidArgumentException(
                "Factory parameter for '{$id}' must accept " . Container::class,
            );
        }

        return new self($closure, $parameter !== null);
    }

    public function invoke(Container $container): mixed
    {
        return $this->receivesContainer
            ? ($this->factory)($container)
            : ($this->factory)();
    }

    private static function acceptsContainer(?ReflectionType $type): bool
    {
        if ($type === null) {
            return true;
        }
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return in_array($type->getName(), ['mixed', 'object'], true);
            }

            return is_a(Container::class, $type->getName(), true);
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if ($member instanceof ReflectionNamedType && $member->getName() === 'null') {
                    continue;
                }
                if (self::acceptsContainer($member)) {
                    return true;
                }
            }

            return false;
        }
        if ($type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $member) {
                if (!self::acceptsContainer($member)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
