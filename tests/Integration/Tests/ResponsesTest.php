<?php

use function GustavPHP\Tests\Integration\createClient;

$client = createClient();

describe('response', function () use ($client) {
    it('renders a directly returned native view', function () use ($client) {
        $response = $client->request('GET', '/responses/direct-view');

        expect($response->getStatusCode())->toBe(202)
            ->and($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=utf-8')
            ->and($response->getHeaderLine('X-View'))->toBe('native')
            ->and((string) $response->getBody())->toContain('<title>Native views</title>')
            ->toContain('&lt;strong&gt;escaped&lt;/strong&gt;')
            ->not->toContain('<strong>escaped</strong>');
    });

    it('keeps the optional base controller view helper', function () use ($client) {
        $response = $client->request('GET', '/responses/view-helper');

        expect($response->getStatusCode())->toBe(200)
            ->and((string) $response->getBody())->toContain('<title>View helper</title>')
            ->toContain('Base remains optional');
    });

    it('can be plain text', function () use ($client) {
        $response = $client->request('GET', '/responses/plaintext');
        expect($response->getBody()->getContents())->toBe('lorem ipsum');
        expect($response->getHeaderLine('Content-Type'))->toBe('text/plain');
        expect($response->getStatusCode())->toBe(200);
    });

    it('can be html', function () use ($client) {
        $response = $client->request('GET', '/responses/html');
        expect($response->getBody()->getContents())->toBe(
            '<h1>lorem ipsum</h1>'
        );
        expect($response->getHeaderLine('Content-Type'))->toBe('text/html');
        expect($response->getStatusCode())->toBe(200);
    });

    it('can be xml', function () use ($client) {
        $response = $client->request('GET', '/responses/xml');
        expect($response->getBody()->getContents())->toBe(
            '<root><lorem>ipsum</lorem></root>'
        );
        expect($response->getHeaderLine('Content-Type'))->toBe('text/xml');
        expect($response->getStatusCode())->toBe(200);
    });

    it('can be json', function () use ($client) {
        $response = $client->request('GET', '/responses/json');
        expect($response->getBody()->getContents())->toBe(
            '{"string":"lorem ipsum","number":123,"boolean":true,"null":null,"array":["lorem","ipsum","dolor","sit","amet"],"object":{"lorem":"ipsum","dolor":"sit","amet":"consectetur"}}'
        );
        expect($response->getHeaderLine('Content-Type'))->toBe(
            'application/json'
        );
        expect($response->getStatusCode())->toBe(200);
    });

    it('can be a redirect', function () use ($client) {
        $response = $client->request('GET', '/responses/redirect');
        expect($response->getHeaderLine('Location'))->toBe('/responses/plaintext');
        expect($response->getStatusCode())->toBe(301);
    });

    it('infers JSON for a directly returned readonly DTO without a marker attribute', function () use ($client) {
        $response = $client->request('GET', '/responses/direct-dto');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and($body)->toBe([
                'id' => 1,
                'name' => 'Dog 1',
                'state' => 'active',
                'nickname' => null,
                'owner' => ['name' => 'Ada'],
                'watchers' => [
                    ['name' => 'Grace'],
                    ['name' => 'Linus'],
                ],
                'labels' => ['friendly', 0, false],
                'rating' => 1.0,
            ])
            ->and((string) $response->getBody())->toContain('"rating":1.0')
            ->not->toContain('secret')
            ->not->toContain('internalNote');
    });

    it('infers JSON for directly returned recursive collections', function () use ($client) {
        $response = $client->request('GET', '/responses/direct-collection');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and($body['state'])->toBe('active')
            ->and($body['dogs'])->toHaveCount(2)
            ->and($body['dogs'][1]['name'])->toBe('Dog 2');
    });

    it('serializes a nullable direct response as JSON null', function () use ($client) {
        $response = $client->request('GET', '/responses/direct-null');

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and((string) $response->getBody())->toBe('null');
    });

    it('serializes direct scalar and backed enum responses', function () use ($client) {
        $boolean = $client->request('GET', '/responses/direct-false');
        $enum = $client->request('GET', '/responses/direct-enum');

        expect($boolean->getStatusCode())->toBe(200)
            ->and((string) $boolean->getBody())->toBe('false')
            ->and((string) $enum->getBody())->toBe('"active"');
    });

    it('uses the same normalizer through the JSON helper', function () use ($client) {
        $response = $client->request('GET', '/responses/dto-helper');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($body['owner'])->toBe(['name' => 'Ada'])
            ->and($body['watchers'])->toHaveCount(2)
            ->and((string) $response->getBody())->not->toContain('secret');
    });

    it('uses the JSON helper for non-default status and headers', function () use ($client) {
        $response = $client->request('GET', '/responses/dto-helper-created');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(201)
            ->and($response->getHeaderLine('X-Response-Mode'))->toBe('explicit')
            ->and($body['state'])->toBe('active')
            ->and($body['owner'])->toBe(['name' => 'Ada']);
    });

    it('preserves legacy serializers, mixed arrays, exclusions, and additional properties', function () use ($client) {
        $response = $client->request('GET', '/responses/legacy-serializer');
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($body)->toBe([
                'items' => [
                    ['name' => 'child'],
                    'kept',
                    0,
                ],
                'name' => 'legacy',
                'extra' => 'included',
            ]);
    });

    it('maps unsupported values, uninitialized fields, and cycles to safe production errors', function (
        string $uri,
    ) use ($client) {
        $response = $client->request('GET', $uri);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(500)
            ->and($body)->toBe([
                'error' => [
                    'status' => 500,
                    'message' => 'Server Error',
                ],
            ])
            ->and((string) $response->getBody())->not->toContain('Closure')
            ->not->toContain('CircularOutput')
            ->not->toContain('UninitializedOutput');
    })->with([
        'unsupported value' => ['/responses/unsupported'],
        'uninitialized field' => ['/responses/uninitialized'],
        'circular reference' => ['/responses/circular'],
    ]);

    it('continues serving after response serialization fails', function () use ($client) {
        $failed = $client->request('GET', '/responses/circular');
        $next = $client->request('GET', '/responses/plaintext');

        expect($failed->getStatusCode())->toBe(500)
            ->and($next->getStatusCode())->toBe(200)
            ->and((string) $next->getBody())->toBe('lorem ipsum');
    });

    it('maps view rendering failures to a safe 500 and keeps serving', function () use ($client) {
        $failed = $client->request('GET', '/responses/view-missing');
        $next = $client->request('GET', '/responses/plaintext');
        $body = json_decode((string) $failed->getBody(), true);

        expect($failed->getStatusCode())->toBe(500)
            ->and($body)->toBe([
                'error' => [
                    'status' => 500,
                    'message' => 'Server Error',
                ],
            ])
            ->and((string) $failed->getBody())->not->toContain('does-not-exist')
            ->and($next->getStatusCode())->toBe(200)
            ->and((string) $next->getBody())->toBe('lorem ipsum');
    });
});
