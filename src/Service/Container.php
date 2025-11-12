<?php

namespace GustavPHP\Gustav\Service;

use Closure;
use GustavPHP\Gustav\Controller\Base;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class Container
{
    protected bool $built = false;
    /**
     * @var array<string, callable(self): mixed|object>
     */
    protected array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    protected array $resolved = [];

    /**
     * @var array<string, bool>
     */
    protected array $resolving = [];

    /**
     * Register dependencies that can later be resolved by the container.
     *
     * @param array<string, callable(self): mixed|object> $definitions Callables may
     * accept the container as their only argument.
     * @return void
     */
    public function addDependency(array $definitions): void
    {
        foreach ($definitions as $id => $factory) {
            if (!is_string($id) || $id === '') {
                throw new InvalidArgumentException(
                    'Dependency id must be a non-empty string',
                );
            }
            if (!is_callable($factory) && !is_object($factory)) {
                throw new InvalidArgumentException(
                    "Definition for '{$id}' must be an object or callable",
                );
            }

            $this->definitions[$id] = $factory;
        }
    }

    /**
     * Finalizes the container configuration. Included for API parity with the
     * previous php-di integration.
     */
    public function build(): void
    {
        $this->built = true;
    }

    /**
     * Create a new instance of the given class with constructor injection.
     *
     * @param class-string<Base> $class
     * @return Base
     */
    public function make(string $class): Base
    {
        if (!$this->built) {
            throw new LogicException('Container not built');
        }

        $instance = $this->autowire($class);
        if (!$instance instanceof Base) {
            throw new LogicException("{$class} must extend " . Base::class);
        }

        return $instance;
    }

    /**
     * Instantiate a class and resolve its constructor dependencies.
     *
     * @param class-string $class
     * @return object
     */
    protected function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Unable to resolve '{$class}'");
        }

        $reflector = new ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException("{$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (
            $constructor === null ||
            $constructor->getNumberOfParameters() === 0
        ) {
            return new $class();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $parameter) => $this->resolveParameter(
                $class,
                $parameter,
            ),
            $constructor->getParameters(),
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Execute a callable definition with optional container injection.
     *
     * @param string $id
     * @param callable $definition
     * @return mixed
     */
    protected function executeDefinition(
        string $id,
        callable $definition,
    ): mixed {
        $callable = Closure::fromCallable($definition);
        $reflection = new ReflectionFunction($callable);
        $parameterCount = $reflection->getNumberOfParameters();

        if ($parameterCount > 1) {
            throw new InvalidArgumentException(
                "Definition for '{$id}' must accept 0 or 1 parameter, {$parameterCount} given",
            );
        }

        return $parameterCount === 0 ? $callable() : $callable($this);
    }

    /**
     * Resolve a dependency by identifier.
     *
     * @param string $id
     * @return mixed
     */
    protected function resolve(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new LogicException("Unable to resolve '{$id}'");
        }
        $this->resolving[$id] = true;

        if (array_key_exists($id, $this->definitions)) {
            $definition = $this->definitions[$id];
            $value = is_callable($definition)
                ? $this->executeDefinition($id, $definition)
                : $definition;
        } else {
            if (!class_exists($id)) {
                throw new InvalidArgumentException("Unable to resolve '{$id}'");
            }
            $value = $this->autowire($id);
        }

        unset($this->resolving[$id]);

        $this->resolved[$id] = $value;

        return $value;
    }

    /**
     * Resolve a single constructor parameter.
     *
     * @param string $context
     * @param ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveParameter(
        string $context,
        ReflectionParameter $parameter,
    ): mixed {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->resolve($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isOptional()) {
            return null;
        }

        throw new InvalidArgumentException(
            "Unable to resolve parameter \${$parameter->getName()} for {$context}::__construct()",
        );
    }
}
