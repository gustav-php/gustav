<?php

use GustavPHP\Gustav\Router\Method;

use function GustavPHP\Tests\Integration\createClient;

$client = createClient();

describe('methods', function () use ($client) {
    it('can return', function (Method $method) use ($client) {
        $response = $client->request(method: $method->value, uri: '/methods');
        expect($response->getBody()->getContents())->toBe($method->value);
        expect($response->getStatusCode())->toBe(200);
    })->with([
        Method::GET,
        Method::POST,
        Method::PUT,
        Method::PATCH,
        Method::DELETE,
        Method::OPTIONS,
    ]);

    it('uses GET handlers for HEAD requests without returning a body', function () use ($client) {
        $response = $client->request(method: Method::HEAD->value, uri: '/responses/plaintext');

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('Content-Type'))->toBe('text/plain')
            ->and((string) $response->getBody())->toBe('');
    });

    it('answers OPTIONS automatically when no explicit handler exists', function () use ($client) {
        $response = $client->request(method: Method::OPTIONS->value, uri: '/responses/plaintext');

        expect($response->getStatusCode())->toBe(204)
            ->and($response->getHeaderLine('Allow'))->toBe('GET, HEAD, OPTIONS')
            ->and((string) $response->getBody())->toBe('');
    });
});
