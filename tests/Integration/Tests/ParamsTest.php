<?php

use function GustavPHP\Tests\Integration\createClient;

$client = createClient();

describe('params', function () use ($client) {
    it('can be in query', function () use ($client) {
        $response = $client->request('GET', '/params/query', [
            'query' => [
                'required' => 'lorem',
                'optional' => 'ipsum',
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body['required'])->toBe('lorem');
        expect($body['optional'])->toBe('ipsum');
    });

    it('can be in headers', function () use ($client) {
        $response = $client->request('GET', '/params/header', [
            'headers' => [
                'required' => 'lorem',
                'optional' => 'ipsum',
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body['required'])->toBe('lorem');
        expect($body['optional'])->toBe('ipsum');
    });

    it('can be in body', function () use ($client) {
        $response = $client->request('POST', '/params/body', [
            'form_params' => [
                'required' => 'lorem',
                'optional' => 'ipsum',
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body['required'])->toBe('lorem');
        expect($body['optional'])->toBe('ipsum');
    });

    it('can be in cookie', function () use ($client) {
        $response = $client->request('GET', '/params/cookie', [
            'cookies' => [
                'required' => 'lorem',
                'optional' => 'ipsum',
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body['required'])->toBe('lorem');
        expect($body['optional'])->toBe('ipsum');
    });

    it('can be in path', function () use ($client) {
        $response = $client->request('GET', '/params/path/lorem');
        $body = json_decode($response->getBody()->getContents(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body['required'])->toBe('lorem');
    });
});
