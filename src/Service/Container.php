<?php

namespace GustavPHP\Gustav\Service;

use DI;
use Exception;
use GustavPHP\Gustav\Application;
use GustavPHP\Gustav\Controller\Base;
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
     * @param array<callable> $definitions
     * @return void
     */
    public function addDependency(array $definitions): void
    {
        $this->builder->addDefinitions($definitions);
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
