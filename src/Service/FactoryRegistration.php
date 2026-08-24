<?php

namespace GustavPHP\Gustav\Service;

use Closure;
use GustavPHP\Gustav\Attribute\{Factory, Service};
use LogicException;
use ReflectionClass;
use ReflectionNamedType;

/** @internal */
final readonly class FactoryRegistration
{
    /**
     * @param class-string $service
     * @param class-string $factory
     */
    private function __construct(
        public string $service,
        public string $factory,
        public Lifetime $lifetime,
    ) {
    }

    /**
     * @param class-string $factory
     */
    public static function compile(string $factory): self
    {
        $reflection = new ReflectionClass($factory);
        $location = "Service factory '{$factory}'";

        if (!$reflection->isInstantiable()) {
            throw new LogicException("{$location} must be instantiable");
        }

        $attributes = $reflection->getAttributes(Factory::class);
        if (count($attributes) !== 1) {
            throw new LogicException("{$location} must declare exactly one #[Factory] attribute");
        }
        if ($reflection->getAttributes(Service::class) !== []) {
            throw new LogicException("{$location} cannot also declare #[Service]");
        }
        if (is_a($factory, Provider::class, true)) {
            throw new LogicException("{$location} cannot also implement " . Provider::class);
        }
        if (!$reflection->hasMethod('__invoke')) {
            throw new LogicException("{$location} must declare a public __invoke() method");
        }

        $invoke = $reflection->getMethod('__invoke');
        if (!$invoke->isPublic() || $invoke->isStatic()) {
            throw new LogicException("{$location} must declare a public non-static __invoke() method");
        }
        if ($invoke->getNumberOfParameters() !== 0) {
            throw new LogicException("{$location}::__invoke() must accept no parameters");
        }

        $returnType = $invoke->getReturnType();
        if ($returnType === null) {
            throw new LogicException("{$location}::__invoke() must declare a return type");
        }
        if (!$returnType instanceof ReflectionNamedType || $returnType->isBuiltin()) {
            throw new LogicException("{$location}::__invoke() must declare one class or interface return type");
        }
        if ($returnType->allowsNull()) {
            throw new LogicException("{$location}::__invoke() return type cannot be nullable");
        }

        $service = $returnType->getName();
        if (!class_exists($service) && !interface_exists($service)) {
            throw new LogicException("{$location}::__invoke() return type '{$service}' does not exist");
        }

        /** @var Factory $metadata */
        $metadata = $attributes[0]->newInstance();

        return new self($service, $factory, $metadata->getLifetime());
    }

    /** @return Closure(Container): object */
    public function resolver(): Closure
    {
        $factory = $this->factory;

        return static function (Container $services) use ($factory): object {
            $instance = $services->make($factory);
            if (!is_callable($instance)) {
                throw new LogicException("Service factory '{$factory}' is no longer invokable");
            }

            $product = $instance();
            if (!is_object($product)) {
                throw new LogicException("Service factory '{$factory}' did not return an object");
            }

            return $product;
        };
    }
}
