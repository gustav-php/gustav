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
});
