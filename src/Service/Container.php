<?php

namespace GustavPHP\Gustav\Service;

use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class Container
{
    private bool $released = false;

    /** @var array<string, mixed> */
    private array $resolved = [];

    /** @var array<string, bool> */
    private array $resolving = [];
    private Container $root;

    private ContainerState $state;

    public function __construct()
    {
        $this->root = $this;
        $this->state = new ContainerState();
    }

    /**
     * Bind an abstraction to an injectable implementation.
     *
     * @param class-string $implementation
     */
    public function bind(
        string $id,
        string $implementation,
        Lifetime $lifetime = Lifetime::Scoped,
    ): self {
        if (!class_exists($implementation)) {
            throw new InvalidArgumentException("Service implementation '{$implementation}' does not exist");
        }
        if (
            (class_exists($id) || interface_exists($id))
            && !is_a($implementation, $id, true)
        ) {
            throw new InvalidArgumentException("{$implementation} must implement or extend {$id}");
        }

        return $this->register($id, $implementation, $lifetime);
    }

    /**
     * Freeze service registration. Calling this method more than once is safe.
     */
    public function build(): void
    {
        $this->assertRoot();
        $this->state->built = true;
    }

    /**
     * Create one isolated application execution scope.
     *
     * @param array<string, mixed> $seed
     */
    public function createScope(array $seed = []): self
    {
        $this->assertRoot();
        $this->assertBuilt();

        $scope = new self();
        $scope->root = $this;
        $scope->state = $this->state;
        $scope->resolved = $seed;

        return $scope;
    }

    /**
     * Resolve a service from the active container.
     */
    public function get(string $id): mixed
    {
        $this->assertActive();
        $this->assertBuilt();

        if ($id === self::class) {
            return $this;
        }
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $definition = $this->state->definitions[$id] ?? null;
        if ($definition === null) {
            if (!class_exists($id)) {
                throw new InvalidArgumentException("Unable to resolve '{$id}'");
            }
            $definition = new Definition(Lifetime::Scoped, $id);
        }

        return match ($definition->lifetime) {
            Lifetime::Singleton => $this->root->resolveSingleton($id, $definition),
            Lifetime::Scoped => $this->resolveScoped($id, $definition),
            Lifetime::Transient => $this->create($id, $definition),
        };
    }

    /**
     * Report whether an identifier has an explicit definition or can be autowired.
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->resolved)
            || array_key_exists($id, $this->state->definitions)
            || class_exists($id);
    }

    /**
     * Create an uncached class instance while resolving its dependencies through
     * the active container.
     *
     * @param class-string $class
     */
    public function make(string $class): object
    {
        $this->assertActive();
        $this->assertBuilt();

        return $this->autowire($class);
    }

    /**
     * Release all references owned by this execution scope.
     */
    public function release(): void
    {
        if ($this->root === $this) {
            throw new LogicException('The application service container cannot be released');
        }

        $this->resolved = [];
        $this->resolving = [];
        $this->released = true;
    }

    /**
     * Register one service instance per HTTP request, console command, or
     * future application execution boundary.
     */
    public function scoped(string $id, mixed $definition = null): self
    {
        return $this->register($id, $definition ?? $id, Lifetime::Scoped);
    }

    /**
     * Keep direct request injection aligned with the request passed down by
     * middleware.
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->assertScope();
        $this->assertActive();
        $this->resolved[ServerRequestInterface::class] = $request;
    }

    /**
     * Register one service shared by the entire application process.
     */
    public function singleton(string $id, mixed $definition = null): self
    {
        return $this->register($id, $definition ?? $id, Lifetime::Singleton);
    }

    /**
     * Register a service that is recreated for every resolution.
     */
    public function transient(string $id, mixed $definition = null): self
    {
        return $this->register($id, $definition ?? $id, Lifetime::Transient);
    }

    private function assertActive(): void
    {
        if ($this->released) {
            throw new LogicException('Application service scope has already been released');
        }
    }

    private function assertBuilt(): void
    {
        if (!$this->state->built) {
            throw new LogicException('Container not built');
        }
    }

    private function assertRoot(): void
    {
        if ($this->root !== $this) {
            throw new LogicException('Services can only be registered on the application container');
        }
    }

    private function assertScope(): void
    {
        if ($this->root === $this) {
            throw new LogicException('This operation requires an active application scope');
        }
    }

    /**
     * Instantiate a class and resolve its constructor dependencies.
     *
     * @param class-string $class
     */
    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Unable to resolve '{$class}'");
        }

        $reflector = $this->state->reflectors[$class] ??= new ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException("{$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflector->newInstance();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($class, $parameter),
            $constructor->getParameters(),
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    private function create(string $id, Definition $definition): mixed
    {
        if (isset($this->resolving[$id])) {
            $chain = implode(' -> ', [...array_keys($this->resolving), $id]);
            throw new LogicException("Circular dependency detected: {$chain}");
        }

        $this->resolving[$id] = true;

        try {
            $resolver = $definition->resolver;
            if (is_string($resolver)) {
                if (!class_exists($resolver)) {
                    throw new LogicException("Service implementation '{$resolver}' is no longer available");
                }
                $value = $this->autowire($resolver);
            } elseif ($resolver instanceof Factory) {
                $value = $resolver->invoke($this);
            } else {
                $value = $resolver;
            }

            if (
                (class_exists($id) || interface_exists($id))
                && !$value instanceof $id
            ) {
                $type = get_debug_type($value);
                throw new InvalidArgumentException("Definition for '{$id}' returned {$type}");
            }

            return $value;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    private function register(string $id, mixed $resolver, Lifetime $lifetime): self
    {
        $this->assertRoot();
        if ($this->state->built) {
            throw new LogicException('Service container is already built');
        }
        if ($id === '') {
            throw new InvalidArgumentException('Service id must be a non-empty string');
        }
        if (is_string($resolver)) {
            if (!class_exists($resolver)) {
                throw new InvalidArgumentException("Service implementation '{$resolver}' does not exist");
            }
            $reflection = new ReflectionClass($resolver);
            if (!$reflection->isInstantiable()) {
                throw new InvalidArgumentException("Service implementation '{$resolver}' is not instantiable");
            }
            if (
                (class_exists($id) || interface_exists($id))
                && !is_a($resolver, $id, true)
            ) {
                throw new InvalidArgumentException("{$resolver} must implement or extend {$id}");
            }
            $this->state->reflectors[$resolver] = $reflection;
        } elseif (
            is_callable($resolver)
            && ($resolver instanceof \Closure || is_array($resolver))
        ) {
            $resolver = Factory::compile($id, $resolver);
        } elseif (!is_object($resolver)) {
            throw new InvalidArgumentException(
                "Definition for '{$id}' must be a class, object, or callable",
            );
        }
        if (
            is_object($resolver)
            && !$resolver instanceof Factory
            && (class_exists($id) || interface_exists($id))
            && !$resolver instanceof $id
        ) {
            throw new InvalidArgumentException(
                "Object definition for '{$id}' must implement or extend {$id}",
            );
        }
        if (is_object($resolver) && !$resolver instanceof Factory && $lifetime !== Lifetime::Singleton) {
            throw new InvalidArgumentException(
                "Object definition for '{$id}' must use the singleton lifetime",
            );
        }

        $this->state->definitions[$id] = new Definition($lifetime, $resolver);

        return $this;
    }

    private function resolveParameter(string $context, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
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

    private function resolveScoped(string $id, Definition $definition): mixed
    {
        if ($this->root === $this) {
            throw new LogicException("Scoped service '{$id}' requires an active application scope");
        }

        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $this->resolved[$id] = $this->create($id, $definition);

        return $this->resolved[$id];
    }

    private function resolveSingleton(string $id, Definition $definition): mixed
    {
        if (array_key_exists($id, $this->state->singletons)) {
            return $this->state->singletons[$id];
        }

        $this->state->singletons[$id] = $this->create($id, $definition);

        return $this->state->singletons[$id];
    }
}
