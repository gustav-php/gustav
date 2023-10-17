<?php

namespace GustavPHP\Gustav\Service;

use DI;
use DI\Definition\Exception\InvalidDefinition;
use DI\DependencyException;
use Exception;
use GustavPHP\Gustav\Application;
use HaydenPierce\ClassFinder\ClassFinder;
use InvalidArgumentException;
use LogicException;

class Container
{
    protected DI\ContainerBuilder $builder;
    protected DI\Container $container;

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
        $this->container = $this->builder->build();
    }

    public function make(string $class): object
    {
        return $this->container->make($class);
    }
}
