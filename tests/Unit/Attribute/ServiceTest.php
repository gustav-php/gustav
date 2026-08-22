<?php

use GustavPHP\Gustav\Attribute\{GlobalMiddleware, Service};
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Tests\Fixtures\Service\ContainerTestContract;

it('describes a discovered service binding and lifetime', function () {
    $service = new Service(
        as: ContainerTestContract::class,
        lifetime: Lifetime::Singleton,
    );

    expect($service->getService())->toBe(ContainerTestContract::class)
        ->and($service->getLifetime())->toBe(Lifetime::Singleton);
});

it('rejects an unknown discovered service abstraction', function () {
    new Service(as: 'MissingService');
})->throws(InvalidArgumentException::class, 'does not exist');

it('describes global middleware priority and lifetime', function () {
    $middleware = new GlobalMiddleware(
        priority: -100,
        lifetime: Lifetime::Transient,
    );

    expect($middleware->getPriority())->toBe(-100)
        ->and($middleware->getLifetime())->toBe(Lifetime::Transient);
});
