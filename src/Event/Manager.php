<?php

namespace GustavPHP\Gustav\Event;

use Exception;
use GustavPHP\Gustav\Attribute\Event;
use ReflectionClass;
use ReflectionException;

class Manager
{
    /**
     * @var array<string,array<Base>>
     */
    protected static array $listeners = [];

    /**
     * Add a listener to the manager.
     *
     * @param class-string<Base> $class
     * @return void
     * @throws ReflectionException
     * @throws ReflectionException
     */
    public static function addListener(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Event::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            self::$listeners[$instance->getEvent()][] = new $class();
        }
    }

    /**
     * Dispatch an event.
     *
     * @param string $event
     * @param array<mixed> $payload
     * @return void
     */
    public static function dispatch(string $event, array $payload): void
    {
        $payload = new Payload($event, $payload);
        if (!array_key_exists($event, self::$listeners)) {
            throw new Exception("No listeners for event: $event");
        }
        foreach (self::$listeners[$event] as $listener) {
            $listener->handle($payload);
        }
    }
}
