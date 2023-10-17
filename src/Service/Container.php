<?php

namespace GustavPHP\Gustav\Service;

use DI;
use DI\Definition\Exception\InvalidDefinition;
use DI\Definition\Helper\CreateDefinitionHelper;
use DI\Definition\Source\DefinitionSource;
use DI\DependencyException;
use Exception;
use GustavPHP\Gustav\Application;
use GustavPHP\Gustav\Controller\Base;
use HaydenPierce\ClassFinder\ClassFinder;
use InvalidArgumentException;
use LogicException;

class Container
{
    protected ?DI\Container $container;
    protected DI\ContainerBuilder $builder;

    /**
     * Container constructor.
     *
     * @param array<string> $namespaces
     * @return void
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws InvalidDefinition
     * @throws DependencyException
     */
    public function __construct(array $namespaces)
    {
        $this->builder = new DI\ContainerBuilder();
        $this->builder
            ->useAttributes(true)
            ->writeProxiesToFile(true, Application::$configuration->cache);

        foreach ($namespaces as $namespace) {
            $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::STANDARD_MODE);
            foreach ($classes as $class) {
                if (is_subclass_of($class, Base::class)) {
                    $this->builder->addDefinitions([$class => DI\create($class)]);
                }
            }
        }
    }

    /**
     * Add a dependency to the container.
     * 
     * @param (string|callable|DefinitionSource)[] $definitions 
     * @return void 
     * @throws LogicException 
     */
    public function addDependency(string|array|DefinitionSource ...$definitions): void
    {
        $this->builder->addDefinitions(...$definitions);
    }

    public function build(): void
    {
        $this->container = $this->builder->build();
    }

    public function make(string $class): Base
    {
        if (!isset($this->container)) {
            throw new LogicException('Container not built');
        }
        return $this->container->make($class);
    }
}
