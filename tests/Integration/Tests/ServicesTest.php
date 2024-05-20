<?php

use function GustavPHP\Tests\Integration\createClient;

use GustavPHP\Tests\Integration\Services\Simple;

$client = createClient();

describe('service', function () use ($client) {
    it('injects simple test', function () use ($client) {
        $response = $client->request('GET', '/services/simple');
        expect($response->getBody()->getContents())->toBe(Simple::TEST_STRING);
        expect($response->getStatusCode())->toBe(200);
    });

    it('injects nested test', function () use ($client) {
        $response = $client->request('GET', '/services/nested');
        expect($response->getBody()->getContents())->toBe(Simple::TEST_STRING);
        expect($response->getStatusCode())->toBe(200);
    });
});
