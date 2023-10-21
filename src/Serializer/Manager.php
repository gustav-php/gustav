<?php

namespace GustavPHP\Gustav\Serializer;

class Manager
{
    /**
     * @var array<class-string<Base>,Entity>
     */
    protected static array $entities = [];
    /**
     *
     * @param class-string<Base> $className
     * @return void
     */
    public static function addEntity(string $className): void
    {
        self::$entities[$className] = new Entity($className);
    }
    public static function getEntity(string $className): Entity
    {
        return self::$entities[$className];
    }
}
