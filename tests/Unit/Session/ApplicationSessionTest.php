<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Session\SessionOptions;
use GustavPHP\Tests\SessionStoreFixtures\ValidApplication\Services\DiscoveredSessionStore;
use Nyholm\Psr7\ServerRequest;

it('uses a discovered session store without imperative application setup', function () {
    DiscoveredSessionStore::$uses = 0;
    $application = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\SessionStoreFixtures\\ValidApplication',
        session: new SessionOptions(),
    ));

    $response = $application->handle(new ServerRequest('GET', '/session-store'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode((string) $response->getBody(), true))->toBe(['visits' => 1])
        ->and(DiscoveredSessionStore::$uses)->toBeGreaterThan(0);
});

it('rejects CSRF-protected routes when sessions are disabled', function () {
    expect(fn () => new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\SessionStoreFixtures\\InvalidApplication',
    )))->toThrow(LogicException::class, 'requires session configuration');
});
