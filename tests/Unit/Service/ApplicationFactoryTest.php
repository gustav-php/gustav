<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Config\Environment;
use GustavPHP\Tests\FactoryFixtures\AdditionalServices\Products\{AdditionalContract, AdditionalProduct};
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Commands\FactoryScopeCommand;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\{ConfiguredProduct, FactoryContract, ScopedProduct, SingletonProduct, TransientProduct};
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Services\{FailingProductFactory, ScopedProductFactory, SingletonProductFactory, TransientProductFactory};
use Nyholm\Psr7\ServerRequest;
use Psr\Log\{LoggerInterface, NullLogger};
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @param list<string> $serviceNamespaces
 */
function createFactoryApplication(array $serviceNamespaces = []): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\Tests\FactoryFixtures\ValidApplication',
        serviceNamespaces: $serviceNamespaces,
        environment: Environment::fromArray(['FACTORY_PREFIX' => 'configured']),
    ));
}

it('discovers a factory and autowires typed configuration and services', function () {
    $services = createFactoryApplication()->services();
    $services->build();
    $scope = $services->createScope();

    $product = $scope->get(FactoryContract::class);

    expect($product)->toBeInstanceOf(ConfiguredProduct::class)
        ->and($product->value())->toBe('configured:dependency');
});

it('honors every factory product lifetime lazily', function () {
    SingletonProductFactory::$calls = 0;
    ScopedProductFactory::$calls = 0;
    TransientProductFactory::$calls = 0;

    $services = createFactoryApplication()->services();
    $services->build();

    expect(SingletonProductFactory::$calls)->toBe(0)
        ->and(ScopedProductFactory::$calls)->toBe(0)
        ->and(TransientProductFactory::$calls)->toBe(0);

    $first = $services->createScope();
    $second = $services->createScope();
    $firstSingleton = $first->get(SingletonProduct::class);
    $secondSingleton = $second->get(SingletonProduct::class);
    $firstScoped = $first->get(ScopedProduct::class);
    $sameFirstScoped = $first->get(ScopedProduct::class);
    $secondScoped = $second->get(ScopedProduct::class);
    $firstTransient = $first->get(TransientProduct::class);
    $secondTransient = $first->get(TransientProduct::class);

    expect($firstSingleton)->toBe($secondSingleton)
        ->and($firstScoped)->toBe($sameFirstScoped)
        ->and($firstScoped)->not->toBe($secondScoped)
        ->and($firstTransient)->not->toBe($secondTransient)
        ->and(SingletonProductFactory::$calls)->toBe(1)
        ->and(ScopedProductFactory::$calls)->toBe(2)
        ->and(TransientProductFactory::$calls)->toBe(2);
});

it('discovers factories from configured service namespaces', function () {
    $services = createFactoryApplication([
        'GustavPHP\Tests\FactoryFixtures\AdditionalServices',
    ])->services();
    $services->build();

    expect($services->createScope()->get(AdditionalContract::class))
        ->toBeInstanceOf(AdditionalProduct::class);
});

it('lets an application factory replace a framework default', function () {
    $services = createFactoryApplication()->services();
    $services->build();

    expect($services->createScope()->get(LoggerInterface::class))
        ->toBeInstanceOf(NullLogger::class);
});

it('releases scoped factory products after requests and commands', function () {
    ScopedProductFactory::$calls = 0;
    $application = createFactoryApplication();

    $first = $application->handle(new ServerRequest('GET', '/factory-scope'));
    $second = $application->handle(new ServerRequest('GET', '/factory-scope'));
    /** @var array{id:int} $firstPayload */
    $firstPayload = json_decode((string) $first->getBody(), true, 512, JSON_THROW_ON_ERROR);
    /** @var array{id:int} $secondPayload */
    $secondPayload = json_decode((string) $second->getBody(), true, 512, JSON_THROW_ON_ERROR);

    expect($firstPayload['id'])->not->toBe($secondPayload['id']);

    FactoryScopeCommand::$productIds = [];
    $tester = new ApplicationTester(createFactoryApplication()->console());
    $tester->run(['command' => 'factory:scope']);
    $tester->run(['command' => 'factory:scope']);

    expect(FactoryScopeCommand::$productIds)->toHaveCount(2)
        ->and(FactoryScopeCommand::$productIds[0])->not->toBe(FactoryScopeCommand::$productIds[1]);
});

it('keeps lazy factory failures production safe and releases the scope', function () {
    FailingProductFactory::$calls = 0;
    $application = createFactoryApplication();

    expect(FailingProductFactory::$calls)->toBe(0);

    $failed = $application->handle(new ServerRequest('GET', '/factory-failure'));
    $next = $application->handle(new ServerRequest('GET', '/factory-scope'));

    expect($failed->getStatusCode())->toBe(500)
        ->and((string) $failed->getBody())->not->toContain('private factory failure')
        ->and(FailingProductFactory::$calls)->toBe(1)
        ->and($next->getStatusCode())->toBe(200);
});
