<?php

namespace GustavPHP\Gustav\Service;

use DI;
use DI\Definition\Exception\InvalidDefinition;
use DI\Definition\Source\DefinitionSource;
use DI\DependencyException;
use Exception;
use GustavPHP\Gustav\Controller\Base;
use GustavPHP\Gustav\{Application, Discovery};
use InvalidArgumentException;
use LogicException;

class Container
{
    protected DI\ContainerBuilder $builder;
    protected ?DI\Container $container = null;

    /**
     * Container constructor.
     *
     * @return void
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws InvalidDefinition
     * @throws DependencyException
     */
    public function __construct()
    {
        $this->builder = new DI\ContainerBuilder();
        $this->builder
            ->useAttributes(true)
            ->useAutowiring(false);

        if (Application::isProduction()) {
            $this->builder
                ->writeProxiesToFile(true, Application::$configuration->cache)
                ->enableCompilation(Application::$configuration->cache);
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
