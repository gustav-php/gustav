<?php

use GustavPHP\Gustav\Http\Exception\HttpException;

use function GustavPHP\Tests\Integration\{createApplication, createClient};

use GustavPHP\Tests\Integration\Middleware\Trace;
use Nyholm\Psr7\ServerRequest;

$client = createClient();

describe('http kernel', function () use ($client) {
    it('handles requests directly without a RoadRunner process', function () use ($client) {
        $response = $client->request('GET', '/responses/plaintext');

        expect($response->getStatusCode())->toBe(200)
            ->and((string) $response->getBody())->toBe('lorem ipsum');
    });

    it('runs global, controller, and route middleware in order and unwinds the response', function () {
        $app = createApplication()->addMiddleware(new Trace('global'));
        $response = $app->handle(new ServerRequest('GET', '/kernel/middleware'));

        expect((string) $response->getBody())->toBe('global,controller,route')
            ->and($response->getHeader('X-Middleware'))->toBe([
                'route-out',
                'controller-out',
                'global-out',
            ]);
    });

    it('keeps legacy request-only middleware working', function () use ($client) {
        $response = $client->request('GET', '/kernel/legacy');

        expect((string) $response->getBody())->toBe('controller,legacy');
    });

    it('short-circuits one request without stopping subsequent requests', function () {
        $app = createApplication();

        $blocked = $app->handle(new ServerRequest('GET', '/kernel/blocked'));
        $next = $app->handle(new ServerRequest('GET', '/responses/plaintext'));

        expect($blocked->getStatusCode())->toBe(429)
            ->and((string) $blocked->getBody())->toBe('blocked')
            ->and($next->getStatusCode())->toBe(200)
            ->and((string) $next->getBody())->toBe('lorem ipsum');
    });

    it('maps HTTP exceptions to status, headers, and a safe JSON body', function () use ($client) {
        $response = $client->request('GET', '/kernel/error');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(418)
            ->and($response->getHeaderLine('X-Error'))->toBe('mapped')
            ->and($body)->toBe([
                'error' => [
                    'status' => 418,
                    'message' => 'Short and stout',
                ],
            ]);
    });

    it('lets application middleware inspect mapped error responses', function () {
        $app = createApplication()->addMiddleware(new Trace('global'));
        $response = $app->handle(new ServerRequest('GET', '/missing'));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->getHeaderLine('X-Middleware'))->toBe('global-out');
    });

    it('does not expose internal exception messages in production', function () use ($client) {
        $response = $client->request('GET', '/kernel/server-error');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(500)
            ->and($body['error']['message'])->toBe('Server Error')
            ->and((string) $response->getBody())->not->toContain('internal secret');
    });

    it('returns 404 and 405 responses with an Allow header', function () use ($client) {
        $missing = $client->request('GET', '/missing');
        $wrongMethod = $client->request('POST', '/kernel/middleware');

        expect($missing->getStatusCode())->toBe(404)
            ->and($wrongMethod->getStatusCode())->toBe(405)
            ->and($wrongMethod->getHeaderLine('Allow'))->toBe('GET');
    });

    it('allows controllers to return a PSR response directly', function () use ($client) {
        $response = $client->request('GET', '/kernel/psr-response');

        expect($response->getStatusCode())->toBe(202)
            ->and($response->getHeaderLine('X-Response'))->toBe('psr')
            ->and((string) $response->getBody())->toBe('accepted');
    });

    it('rejects invalid HTTP exception status codes', function () {
        new HttpException(200);
    })->throws(InvalidArgumentException::class);
});
