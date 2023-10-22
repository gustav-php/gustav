<?php

namespace GustavPHP\Gustav;

use Exception;
use HaydenPierce\ClassFinder\ClassFinder;

class Discovery
{
    /**
     * @return iterable<class-string<Controller\Base>>
     * @throws Exception
     */
    public static function discoverController(): iterable
    {
        foreach (self::discover('Routes', Controller\Base::class, 'routeNamespaces') as $route) {
            /**
             * @var class-string<Controller\Base> $route
             */
            yield $route;
        }
    }
    /**
     * @return iterable<class-string<Event\Base>>
     * @throws Exception
     */
    public static function discoverEvents(): iterable
    {
        foreach (self::discover('Events', Event\Base::class, 'eventNamespaces') as $event) {
            /**
             * @var class-string<Event\Base> $event
             */
            yield $event;
        }
    }
    /**
     * @return iterable<class-string<Serializer\Base>>
     * @throws Exception
     */
    public static function discoverSerializers(): iterable
    {
        foreach (self::discover('Serializers', Serializer\Base::class, 'serializerNamespaces') as $serializer) {
            /**
             * @var class-string<Serializer\Base> $serializer
             */
            yield $serializer;
        }
    }
    /**
     * @param string $namespace
     * @param class-string $base
     * @param string $configurationKey
     * @return iterable<class-string>
     * @throws Exception
     */
    private static function discover(string $namespace, string $base, string $configurationKey): iterable
    {
        $default = Application::$configuration->namespace . '\\' . $namespace;
        foreach ([
            $default,
            ...Application::$configuration->{$configurationKey}
        ] as $namespace) {
            $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::STANDARD_MODE);
            foreach ($classes as $class) {
                if (is_subclass_of($class, $base)) {
                    yield $class;
                }
            }
        }
    }
}
