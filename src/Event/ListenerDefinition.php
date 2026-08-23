<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Attribute\Listener;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;

/** @internal */
final readonly class ListenerDefinition
{
    /**
     * @param class-string $listener
     * @param class-string $event
     */
    public function __construct(
        public string $listener,
        public string $event,
        public int $priority,
    ) {
    }

    /** @param class-string $listener */
    public static function compile(string $listener): self
    {
        $reflection = new ReflectionClass($listener);
        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("Event listener '{$listener}' must be instantiable");
        }
        if (!$reflection->hasMethod('__invoke')) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must declare a public __invoke() method",
            );
        }

        $invoke = $reflection->getMethod('__invoke');
        if (!$invoke->isPublic() || $invoke->isStatic()) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must declare a public non-static __invoke() method",
            );
        }
        if ($invoke->getNumberOfParameters() !== 1) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must accept exactly one event parameter",
            );
        }

        $parameter = $invoke->getParameters()[0];
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must declare one event class or interface",
            );
        }
        if ($type->allowsNull()) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' event parameter cannot be nullable",
            );
        }
        if ($parameter->isVariadic() || $parameter->isPassedByReference()) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' event parameter must be a regular value parameter",
            );
        }

        $event = $type->getName();
        if (!class_exists($event) && !interface_exists($event)) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' references unknown event type '{$event}'",
            );
        }

        $return = $invoke->getReturnType();
        if (!$return instanceof ReflectionNamedType || $return->getName() !== 'void') {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must declare void as its return type",
            );
        }

        $attributes = $reflection->getAttributes(Listener::class);
        if (count($attributes) !== 1) {
            throw new InvalidArgumentException(
                "Event listener '{$listener}' must declare exactly one #[Listener] attribute",
            );
        }
        $metadata = $attributes[0]->newInstance();

        return new self($listener, $event, $metadata->priority);
    }
}
