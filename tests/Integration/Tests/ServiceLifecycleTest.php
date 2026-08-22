<?php

use function GustavPHP\Tests\Integration\createApplication;

use GustavPHP\Tests\Integration\Middleware\GlobalInjected;
use Nyholm\Psr7\ServerRequest;

describe('application services', function () {
    it('resolves configured services and injectable middleware with explicit lifetimes', function () {
        $app = createApplication()->addMiddleware(GlobalInjected::class);

        $firstResponse = $app->handle(new ServerRequest('GET', '/services/lifecycle'));
        $secondResponse = $app->handle(new ServerRequest('GET', '/services/lifecycle'));
        $first = json_decode((string) $firstResponse->getBody(), true);
        $second = json_decode((string) $secondResponse->getBody(), true);

        expect($firstResponse->getStatusCode())->toBe(200)
            ->and($first['greeting'])->toBe('configured')
            ->and($first['request'])->toBe($first['middleware'])
            ->and($first['transientsDiffer'])->toBeTrue()
            ->and($first['path'])->toBe('/services/lifecycle')
            ->and($first['request'])->not->toBe($second['request'])
            ->and($first['singleton'])->toBe($second['singleton'])
            ->and($firstResponse->getHeaderLine('X-Singleton-Service'))->toBe((string) $first['singleton'])
            ->and($secondResponse->getHeaderLine('X-Singleton-Service'))->toBe((string) $second['singleton']);
    });

    it('releases request services after failures', function () {
        $app = createApplication();

        $failed = $app->handle(new ServerRequest('GET', '/services/lifecycle/error'));
        $next = $app->handle(new ServerRequest('GET', '/services/lifecycle'));
        $failure = json_decode((string) $failed->getBody(), true);
        $body = json_decode((string) $next->getBody(), true);

        expect($failed->getStatusCode())->toBe(418)
            ->and($next->getStatusCode())->toBe(200)
            ->and($failure['error']['message'])->not->toBe((string) $body['request']);
    });

    it('freezes service configuration when request handling begins', function () {
        $app = createApplication();
        $app->handle(new ServerRequest('GET', '/responses/plaintext'));

        $app->services()->singleton(stdClass::class);
    })->throws(LogicException::class, 'already built');
});
