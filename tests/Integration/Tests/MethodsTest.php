<?php

use GustavPHP\Gustav\Router\Method;

$client = new GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:5173']);

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
        Method::OPTIONS
    ]);
});
