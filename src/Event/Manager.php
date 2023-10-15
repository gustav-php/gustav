<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Attribute\Event;
use ReflectionClass;

class Manager
{
    /**
     * @var array<string,array<Base>>
     */
    protected static array $listeners = [];

    /**
     * @param string $class
     * @return void
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
     * @param string $event
     * @param array $payload
     * @return void
     */
    public static function dispatch(string $event, array $payload): void
    {
        $payload = new Payload($event, $payload);
        if (!isset(self::$listeners[$event])) {
            return;
        }
        foreach (self::$listeners[$event] as $listener) {
            $listener->handle($payload);
        }
    }
}
