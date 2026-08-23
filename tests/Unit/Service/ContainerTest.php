<?php

use GustavPHP\Gustav\Service\Container;
use GustavPHP\Tests\Fixtures\Service\{ContainerTestAutowiredController, ContainerTestCircularController, ContainerTestContract, ContainerTestDefinitionController, ContainerTestEmptyController, ContainerTestImplementation, ContainerTestInvalidSingleton, ContainerTestInvokableService, ContainerTestNestedDependency, ContainerTestPlainDependency, ContainerTestProvidedService, ContainerTestRequestService, ContainerTestScopedConsumer, ContainerTestSingletonService, ContainerTestTransientService, ContainerTestUnresolvableController};
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

it('requires the container to be built before resolving controllers', function () {
    $container = new Container();
    $container->make(ContainerTestEmptyController::class);
})->throws(LogicException::class, 'Container not built');

it('autowires nested dependencies and caches resolved services', function () {
    $container = new Container();
    $container->build();
    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    /** @var ContainerTestAutowiredController $first */
    $first = $scope->make(ContainerTestAutowiredController::class);
    /** @var ContainerTestAutowiredController $second */
    $second = $scope->make(ContainerTestAutowiredController::class);

    expect($first)->toBeInstanceOf(ContainerTestAutowiredController::class);
    expect($first->dependency)->toBeInstanceOf(ContainerTestNestedDependency::class);
    expect($first->dependency->plain)->toBeInstanceOf(ContainerTestPlainDependency::class);
    expect($first->dependency)->toBe($second->dependency);
    expect($first->dependency->plain)->toBe($second->dependency->plain);
});

it('uses factories and injects the active request container when requested', function () {
    $container = new Container();
    $captured = null;

    $container->request(
        ContainerTestProvidedService::class,
        function (Container $current) use (&$captured) {
            $captured = $current;
            return new ContainerTestProvidedService('custom');
        },
    );

    $container->build();
    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    /** @var ContainerTestDefinitionController $controller */
    $controller = $scope->make(ContainerTestDefinitionController::class);

    expect($controller->provided->value)->toBe('custom');
    expect($captured)->toBe($scope);
});

it('rejects invalid dependency identifiers', function () {
    $container = new Container();
    $container->request('', fn () => null);
})->throws(InvalidArgumentException::class);

it('rejects definitions that are neither callable nor objects', function () {
    $container = new Container();
    $container->request('foo', 'bar');
})->throws(InvalidArgumentException::class);

it('detects circular dependencies when instantiating controllers', function () {
    $container = new Container();
    $container->build();
    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    $scope->make(ContainerTestCircularController::class);
})->throws(LogicException::class, 'Circular dependency detected');

it('throws when a constructor parameter cannot be resolved', function () {
    $container = new Container();
    $container->build();
    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    $scope->make(ContainerTestUnresolvableController::class);
})->throws(InvalidArgumentException::class);

it('binds interfaces to concrete implementations', function () {
    $container = new Container();
    $container->bind(ContainerTestContract::class, ContainerTestImplementation::class);
    $container->build();

    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    expect($scope->get(ContainerTestContract::class))
        ->toBeInstanceOf(ContainerTestImplementation::class)
        ->value()->toBe('implementation');
});

it('honors singleton, request, and transient lifetimes', function () {
    $container = new Container();
    $container
        ->singleton(ContainerTestSingletonService::class)
        ->request(ContainerTestRequestService::class)
        ->transient(ContainerTestTransientService::class);
    $container->build();

    $first = $container->createRequestScope(new ServerRequest('GET', '/first'));
    $second = $container->createRequestScope(new ServerRequest('GET', '/second'));

    expect($first->get(ContainerTestSingletonService::class))
        ->toBe($first->get(ContainerTestSingletonService::class))
        ->toBe($second->get(ContainerTestSingletonService::class))
        ->and($first->get(ContainerTestRequestService::class))
        ->toBe($first->get(ContainerTestRequestService::class))
        ->not->toBe($second->get(ContainerTestRequestService::class))
        ->and($first->get(ContainerTestTransientService::class))
        ->not->toBe($first->get(ContainerTestTransientService::class));
});

it('injects the current request and isolates autowired services by request', function () {
    $container = new Container();
    $container->build();

    $request = new ServerRequest('GET', '/first');
    $first = $container->createRequestScope($request);
    $second = $container->createRequestScope(new ServerRequest('GET', '/second'));

    $firstConsumer = $first->get(ContainerTestScopedConsumer::class);
    $secondConsumer = $second->get(ContainerTestScopedConsumer::class);

    expect($first->has(ServerRequestInterface::class))->toBeTrue()
        ->and($firstConsumer->request)->toBe($request)
        ->and($firstConsumer)->toBe($first->get(ContainerTestScopedConsumer::class))
        ->and($firstConsumer)->not->toBe($secondConsumer)
        ->and($firstConsumer->service)->not->toBe($secondConsumer->service);
});

it('seeds request-scoped values without replacing the active request', function () {
    $container = new Container();
    $container->build();
    $request = new ServerRequest('GET', '/seeded');
    $seeded = new ContainerTestPlainDependency();
    $scope = $container->createRequestScope($request, [
        ContainerTestPlainDependency::class => $seeded,
    ]);

    expect($scope->get(ContainerTestPlainDependency::class))->toBe($seeded)
        ->and($scope->get(ServerRequestInterface::class))->toBe($request);
});

it('prevents singletons from capturing request-scoped services', function () {
    $container = new Container();
    $container
        ->request(ContainerTestRequestService::class)
        ->singleton(ContainerTestInvalidSingleton::class);
    $container->build();

    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));
    $scope->get(ContainerTestInvalidSingleton::class);
})->throws(LogicException::class, 'requires an active request scope');

it('freezes registrations after the container is built', function () {
    $container = new Container();
    $container->build();
    $container->singleton(ContainerTestSingletonService::class);
})->throws(LogicException::class, 'already built');

it('cannot use a released request scope', function () {
    $container = new Container();
    $container->build();
    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));
    $scope->release();

    $scope->get(ContainerTestRequestService::class);
})->throws(LogicException::class, 'released');

it('validates factory signatures during registration', function () {
    $container = new Container();
    $container->request('invalid', fn (string $value): string => $value);
})->throws(InvalidArgumentException::class, Container::class);

it('rejects shared object instances outside the singleton lifetime', function () {
    $container = new Container();
    $container->request(
        ContainerTestProvidedService::class,
        new ContainerTestProvidedService('shared'),
    );
})->throws(InvalidArgumentException::class, 'singleton lifetime');

it('caches null factory results according to their lifetime', function () {
    $container = new Container();
    $requestCalls = 0;
    $singletonCalls = 0;
    $container
        ->request('nullable.request', function () use (&$requestCalls) {
            $requestCalls++;
            return;
        })
        ->singleton('nullable.singleton', function () use (&$singletonCalls) {
            $singletonCalls++;
            return;
        });
    $container->build();

    $first = $container->createRequestScope(new ServerRequest('GET', '/first'));
    $second = $container->createRequestScope(new ServerRequest('GET', '/second'));

    $first->get('nullable.request');
    $first->get('nullable.request');
    $second->get('nullable.request');
    $first->get('nullable.singleton');
    $second->get('nullable.singleton');

    expect($requestCalls)->toBe(2)
        ->and($singletonCalls)->toBe(1);
});

it('registers invokable objects as singleton instances instead of factories', function () {
    $container = new Container();
    $service = new ContainerTestInvokableService();
    $container->singleton(ContainerTestInvokableService::class, $service);
    $container->build();

    $scope = $container->createRequestScope(new ServerRequest('GET', '/'));

    expect($scope->get(ContainerTestInvokableService::class))->toBe($service);
});

it('validates singleton object types during registration', function () {
    $container = new Container();
    $container->singleton(ContainerTestContract::class, new stdClass());
})->throws(InvalidArgumentException::class, 'must implement or extend');
