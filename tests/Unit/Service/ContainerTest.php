<?php

use GustavPHP\Gustav\Service\Container;
use GustavPHP\Tests\Fixtures\Service\{ContainerTestAutowiredController, ContainerTestCircularController, ContainerTestDefinitionController, ContainerTestEmptyController, ContainerTestNestedDependency, ContainerTestPlainDependency, ContainerTestProvidedService, ContainerTestUnresolvableController};

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
