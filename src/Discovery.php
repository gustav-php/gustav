<?php

namespace GustavPHP\Gustav;

use Exception;
use GustavPHP\Gustav\Service\{Provider, Registration};
use HaydenPierce\ClassFinder\ClassFinder;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;

class Discovery
{
    /**
     * @return iterable<class-string>
     * @throws Exception
     */
    public static function discoverCommands(): iterable
    {
        foreach (self::discoverClasses('Commands', 'commandNamespaces') as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->getAttributes(Attribute\Command::class) === []) {
                continue;
            }

            yield $class;
        }
    }

    /**
     * @return iterable<class-string>
     * @throws Exception
     */
    public static function discoverConfigurations(): iterable
    {
        foreach (self::discoverClasses('Config', 'configurationNamespaces') as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->getAttributes(Attribute\Config::class) === []) {
                continue;
            }

            yield $class;
        }
    }

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
     * @return array<int, array{
     *     class: class-string<MiddlewareInterface>,
     *     lifetime: Service\Lifetime,
     *     priority: int
     * }>
     * @throws Exception
     */
    public static function discoverMiddlewares(): array
    {
        $middlewares = [];

        foreach (self::discoverClasses('Middlewares', 'middlewareNamespaces') as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Attribute\GlobalMiddleware::class);
            if ($attributes === []) {
                continue;
            }
            if (!is_a($class, MiddlewareInterface::class, true)) {
                throw new InvalidArgumentException(
                    "Global middleware '{$class}' must implement " . MiddlewareInterface::class,
                );
            }

            $metadata = $attributes[0]->newInstance();
            $middlewares[] = [
                'class' => $class,
                'lifetime' => $metadata->getLifetime(),
                'priority' => $metadata->getPriority(),
            ];
        }

        usort(
            $middlewares,
            fn (array $left, array $right): int => $left['priority'] <=> $right['priority']
                ?: strcmp($left['class'], $right['class']),
        );

        return $middlewares;
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
     * @return iterable<class-string<Provider>>
     * @throws Exception
     */
    public static function discoverServiceProviders(): iterable
    {
        foreach (self::discoverClasses('Services', 'serviceNamespaces') as $class) {
            if (!is_a($class, Provider::class, true)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            if (
                !$reflection->isInstantiable()
                || ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0)
            ) {
                throw new InvalidArgumentException(
                    "Service provider '{$class}' must have a public zero-argument constructor",
                );
            }

            yield $class;
        }
    }

    /**
     * @return iterable<Registration>
     * @throws Exception
     */
    public static function discoverServices(): iterable
    {
        foreach (self::discoverClasses('Services', 'serviceNamespaces') as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Attribute\Service::class);
            if ($attributes === []) {
                continue;
            }

            $metadata = $attributes[0]->newInstance();

            yield new Registration(
                $metadata->getService() ?? $class,
                $class,
                $metadata->getLifetime(),
            );
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
            $classes = ClassFinder::getClassesInNamespace($namespace);
            foreach ($classes as $class) {
                if (is_subclass_of($class, $base)) {
                    yield $class;
                }
            }
        }
    }

    /**
     * @return iterable<class-string>
     * @throws Exception
     */
    private static function discoverClasses(string $namespace, string $configurationKey): iterable
    {
        $seen = [];
        $default = Application::$configuration->namespace . '\\' . $namespace;

        foreach ([$default, ...Application::$configuration->{$configurationKey}] as $current) {
            foreach (ClassFinder::getClassesInNamespace($current, ClassFinder::RECURSIVE_MODE) as $class) {
                if (
                    !class_exists($class)
                    && !interface_exists($class)
                    && !trait_exists($class)
                ) {
                    throw new InvalidArgumentException("Discovered class '{$class}' does not exist");
                }
                if (isset($seen[$class])) {
                    continue;
                }
                $seen[$class] = true;

                yield $class;
            }
        }
    }
}
