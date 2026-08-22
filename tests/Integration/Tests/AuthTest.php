<?php

use GustavPHP\Gustav\Auth\{ApiKeyAuth, BasicAuth, BearerAuth};

use function GustavPHP\Tests\Integration\createClient;

use Nyholm\Psr7\ServerRequest;

$client = createClient();

describe('authentication', function () use ($client) {
    it('parses Basic, Bearer, and API key credentials', function () {
        $basic = BasicAuth::fromRequest(new ServerRequest('GET', '/', [
            'Authorization' => 'basic ' . base64_encode('gustav:secret'),
        ]));
        $bearer = BearerAuth::fromRequest(new ServerRequest('GET', '/', [
            'Authorization' => 'bearer token-123',
        ]));
        $apiKey = ApiKeyAuth::fromHeader(new ServerRequest('GET', '/', [
            'X-API-Key' => '0',
        ]), 'X-API-Key');

        expect($basic->getUsername())->toBe('gustav')
            ->and($basic->getPassword())->toBe('secret')
            ->and($bearer->getToken())->toBe('token-123')
            ->and($apiKey->getKey())->toBe('0');
    });

    it('authenticates and injects a typed identity', function () use ($client) {
        $response = $client->request('GET', '/kernel/auth', [
            'headers' => ['Authorization' => 'Bearer valid-token'],
        ]);

        expect($response->getStatusCode())->toBe(200)
            ->and(json_decode((string) $response->getBody(), true))->toBe([
                'id' => 'user-123',
                'roles' => ['reader'],
            ]);
    });

    it('returns a challenge when credentials are missing', function () use ($client) {
        $response = $client->request('GET', '/kernel/auth');

        expect($response->getStatusCode())->toBe(401)
            ->and($response->getHeaderLine('WWW-Authenticate'))->toBe('Bearer');
    });

    it('returns 401 for invalid credentials without exposing a server error', function () use ($client) {
        $response = $client->request('GET', '/kernel/auth', [
            'headers' => ['Authorization' => 'Bearer wrong-token'],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(401)
            ->and($response->getHeaderLine('WWW-Authenticate'))->toContain('invalid_token')
            ->and($body['error']['message'])->toBe('Bearer token is invalid');
    });

    it('rejects identity injection when no authentication middleware ran', function () use ($client) {
        $response = $client->request('GET', '/kernel/auth/missing');

        expect($response->getStatusCode())->toBe(401);
    });

    it('maps authorization failures to 403', function () use ($client) {
        $response = $client->request('GET', '/kernel/forbidden');

        expect($response->getStatusCode())->toBe(403);
    });
});
