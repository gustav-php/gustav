<?php

use GustavPHP\Gustav\Security\CsrfTokenManager;
use GustavPHP\Tests\Integration\Client;

use function GustavPHP\Tests\Integration\createClient;

use Psr\Http\Message\ResponseInterface;

/** @return array{token:string,id:string,cookies:array{gustav_session:string}} */
function integrationSessionCredentials(Client $client): array
{
    $response = $client->request('GET', '/sessions/token');
    $body = json_decode((string) $response->getBody(), true);
    $id = integrationSessionCookieId($response);

    return [
        'token' => $body['token'],
        'id' => $id,
        'cookies' => ['gustav_session' => $id],
    ];
}

function integrationSessionCookieId(ResponseInterface $response): string
{
    $cookie = $response->getHeader('Set-Cookie')[0] ?? '';
    preg_match('/^gustav_session=([^;]*)/', $cookie, $matches);

    return $matches[1] ?? '';
}

function integrationJson(ResponseInterface $response): array
{
    $body = json_decode((string) $response->getBody(), true);
    expect($body)->toBeArray();

    return $body;
}

it('keeps sessions lazy on safe requests that only read missing state', function () {
    $response = createClient()->request('GET', '/sessions/value');

    expect($response->getStatusCode())->toBe(200)
        ->and(integrationJson($response))->toBe(['value' => null])
        ->and($response->hasHeader('Set-Cookie'))->toBeFalse();
});

it('returns a structured 403 before a protected controller runs', function (array $options) {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $options['cookies'] = $credentials['cookies'];
    $response = $client->request('POST', '/sessions/value', $options);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(integrationJson($response))->toBe([
            'error' => [
                'status' => 403,
                'message' => 'CSRF token is invalid',
            ],
        ]);
})->with([
    'missing token' => [['form_params' => ['value' => 'blocked']]],
    'invalid token' => [[
        'headers' => [CsrfTokenManager::HEADER => str_repeat('a', 43)],
        'json' => ['value' => 'blocked'],
    ]],
]);

it('preserves body syntax errors while looking for a form token', function (string $contentType, string $body, int $status) {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $response = $client->request('POST', '/sessions/value', [
        'cookies' => $credentials['cookies'],
        'headers' => ['Content-Type' => $contentType],
        'body' => $body,
    ]);

    expect($response->getStatusCode())->toBe($status);
})->with([
    'malformed JSON' => ['application/json', '{"_token":', 400],
    'unsupported body media type' => ['text/plain', 'not-form-data', 415],
]);

it('accepts a form token and strips it before request binding', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $response = $client->request('POST', '/sessions/value', [
        'cookies' => $credentials['cookies'],
        'form_params' => [
            CsrfTokenManager::FIELD => $credentials['token'],
            'value' => 'form',
        ],
    ]);

    expect($response->getStatusCode())->toBe(200)
        ->and(integrationJson($response))->toBe([
            'value' => 'form',
            'token_present' => false,
        ]);
});

it('accepts a CSRF header for JSON requests and persists between application instances', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $stored = $client->request('POST', '/sessions/value', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
        'json' => ['value' => 'json'],
    ]);
    $restored = $client->request('GET', '/sessions/value', [
        'cookies' => $credentials['cookies'],
    ]);

    expect($stored->getStatusCode())->toBe(200)
        ->and(integrationJson($stored))->toBe(['value' => 'json', 'token_present' => false])
        ->and(integrationJson($restored))->toBe(['value' => 'json']);
});

it('exposes flash data for exactly the next request', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $stored = $client->request('POST', '/sessions/flash', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
        'json' => ['message' => 'Saved'],
    ]);
    $first = $client->request('GET', '/sessions/flash', ['cookies' => $credentials['cookies']]);
    $second = $client->request('GET', '/sessions/flash', ['cookies' => $credentials['cookies']]);

    expect($stored->getStatusCode())->toBe(200)
        ->and(integrationJson($first))->toBe(['notice' => 'Saved'])
        ->and(integrationJson($second))->toBe(['notice' => null]);
});

it('regenerates the identifier while preserving state and invalidating the old id', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $client->request('POST', '/sessions/value', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
        'json' => ['value' => 'preserved'],
    ]);
    $regenerated = $client->request('POST', '/sessions/regenerate', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
    ]);
    $body = integrationJson($regenerated);
    $newId = integrationSessionCookieId($regenerated);
    $old = $client->request('GET', '/sessions/value', ['cookies' => $credentials['cookies']]);
    $current = $client->request('GET', '/sessions/value', [
        'cookies' => ['gustav_session' => $newId],
    ]);

    expect($body)->toBe([
        'old' => $credentials['id'],
        'new' => $newId,
        'value' => 'preserved',
    ])->and($newId)->not->toBe($credentials['id'])
        ->and(integrationJson($old))->toBe(['value' => null])
        ->and(integrationJson($current))->toBe(['value' => 'preserved']);
});

it('invalidates session state and expires the cookie', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $response = $client->request('POST', '/sessions/invalidate', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
    ]);
    $after = $client->request('GET', '/sessions/value', ['cookies' => $credentials['cookies']]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getHeaderLine('Set-Cookie'))->toContain('Max-Age=0')
        ->and(integrationJson($after))->toBe(['value' => null]);
});

it('does not commit session mutations from a failed request and keeps serving', function () {
    $client = createClient();
    $credentials = integrationSessionCredentials($client);
    $client->request('POST', '/sessions/value', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
        'json' => ['value' => 'before'],
    ]);
    $failed = $client->request('POST', '/sessions/fail', [
        'cookies' => $credentials['cookies'],
        'headers' => [CsrfTokenManager::HEADER => $credentials['token']],
    ]);
    $after = $client->request('GET', '/sessions/value', ['cookies' => $credentials['cookies']]);

    expect($failed->getStatusCode())->toBe(500)
        ->and(integrationJson($failed))->toBe([
            'error' => [
                'status' => 500,
                'message' => 'Server Error',
            ],
        ])->and((string) $failed->getBody())->not->toContain('private session failure')
        ->and(integrationJson($after))->toBe(['value' => 'before']);
});
