<?php

use GustavPHP\Gustav\Controller\Base;
use GustavPHP\Gustav\Service\Container;

it('requires the container to be built before resolving controllers', function () {
    $container = new Container();
    $container->make(ContainerTestEmptyController::class);
})->throws(LogicException::class, 'Container not built');

it('autowires nested dependencies and caches resolved services', function () {
    $container = new Container();
    $container->build();

    /** @var ContainerTestAutowiredController $first */
    $first = $container->make(ContainerTestAutowiredController::class);
    /** @var ContainerTestAutowiredController $second */
    $second = $container->make(ContainerTestAutowiredController::class);

    expect($first)->toBeInstanceOf(ContainerTestAutowiredController::class);
    expect($first->dependency)->toBeInstanceOf(ContainerTestNestedDependency::class);
    expect($first->dependency->plain)->toBeInstanceOf(ContainerTestPlainDependency::class);
    expect($first->dependency)->toBe($second->dependency);
    expect($first->dependency->plain)->toBe($second->dependency->plain);
});

it('uses callable definitions and injects the container into factories when requested', function () {
    $container = new Container();
    $captured = null;

    $container->addDependency([
        ContainerTestProvidedService::class => function (Container $current) use (&$captured) {
            $captured = $current;
            return new ContainerTestProvidedService('custom');
        },
    ]);

    $container->build();

    /** @var ContainerTestDefinitionController $controller */
    $controller = $container->make(ContainerTestDefinitionController::class);

    expect($controller->provided->value)->toBe('custom');
    expect($captured)->toBe($container);
});

it('rejects invalid dependency identifiers', function () {
    $container = new Container();
    $container->addDependency([
        '' => fn () => null,
    ]);
})->throws(InvalidArgumentException::class);

it('rejects definitions that are neither callable nor objects', function () {
    $container = new Container();
    $container->addDependency([
        'foo' => 'bar',
    ]);
})->throws(InvalidArgumentException::class);

it('detects circular dependencies when instantiating controllers', function () {
    $container = new Container();
    $container->build();

    $container->make(ContainerTestCircularController::class);
})->throws(LogicException::class);

it('throws when a constructor parameter cannot be resolved', function () {
    $container = new Container();
    $container->build();

    $container->make(ContainerTestUnresolvableController::class);
})->throws(InvalidArgumentException::class);

if (!class_exists(ContainerTestPlainDependency::class)) {
    class ContainerTestPlainDependency
    {
    }
}

if (!class_exists(ContainerTestNestedDependency::class)) {
    class ContainerTestNestedDependency
    {
        public function __construct(public ContainerTestPlainDependency $plain)
        {
        }
    }
}

if (!class_exists(ContainerTestAutowiredController::class)) {
    class ContainerTestAutowiredController extends Base
    {
        public function __construct(public ContainerTestNestedDependency $dependency)
        {
        }
    }
}

if (!class_exists(ContainerTestProvidedService::class)) {
    class ContainerTestProvidedService
    {
        public function __construct(public string $value)
        {
        }
    }
}

if (!class_exists(ContainerTestDefinitionController::class)) {
    class ContainerTestDefinitionController extends Base
    {
        public function __construct(public ContainerTestProvidedService $provided)
        {
        }
    }
}

if (!class_exists(ContainerTestEmptyController::class)) {
    class ContainerTestEmptyController extends Base
    {
    }
}

if (!class_exists(ContainerTestCircularA::class)) {
    class ContainerTestCircularA
    {
        public function __construct(public ContainerTestCircularB $b)
        {
        }
    }
}

if (!class_exists(ContainerTestCircularB::class)) {
    class ContainerTestCircularB
    {
        public function __construct(public ContainerTestCircularA $a)
        {
        }
    }
}

if (!class_exists(ContainerTestCircularController::class)) {
    class ContainerTestCircularController extends Base
    {
        public function __construct(public ContainerTestCircularA $a)
        {
        }
    }
}

if (!class_exists(ContainerTestUnresolvableController::class)) {
    class ContainerTestUnresolvableController extends Base
    {
        public function __construct(public string $value)
        {
        }
    }
}
