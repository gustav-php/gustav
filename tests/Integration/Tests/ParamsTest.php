<?php

use function GustavPHP\Tests\Integration\{createApplication, createClient};

use Nyholm\Psr7\{ServerRequest, Stream};

$client = createClient();

describe('request input binding', function () use ($client) {
    it('preserves successful binding for every scalar input source', function () use ($client) {
        $query = $client->request('GET', '/params/query', [
            'query' => ['required' => 'query-value', 'optional' => 'query-optional'],
        ]);
        $header = $client->request('GET', '/params/header', [
            'headers' => ['required' => 'header-value', 'optional' => 'header-optional'],
        ]);
        $body = $client->request('POST', '/params/body', [
            'form_params' => ['required' => 'body-value', 'optional' => 'body-optional'],
        ]);
        $cookie = $client->request('GET', '/params/cookie', [
            'cookies' => ['required' => 'cookie-value', 'optional' => 'cookie-optional'],
        ]);
        $path = $client->request('GET', '/params/path/path-value');

        expect($query->getStatusCode())->toBe(200)
            ->and(json_decode((string) $query->getBody(), true))->toMatchArray([
                'required' => 'query-value',
                'optional' => 'query-optional',
            ])
            ->and($header->getStatusCode())->toBe(200)
            ->and(json_decode((string) $header->getBody(), true))->toMatchArray([
                'required' => 'header-value',
                'optional' => 'header-optional',
            ])
            ->and($body->getStatusCode())->toBe(200)
            ->and(json_decode((string) $body->getBody(), true))->toMatchArray([
                'required' => 'body-value',
                'optional' => 'body-optional',
            ])
            ->and($cookie->getStatusCode())->toBe(200)
            ->and(json_decode((string) $cookie->getBody(), true))->toMatchArray([
                'required' => 'cookie-value',
                'optional' => 'cookie-optional',
            ])
            ->and(json_decode((string) $path->getBody(), true))->toBe(['required' => 'path-value']);
    });

    it('uses PHP defaults when optional values are omitted', function () use ($client) {
        $responses = [
            $client->request('GET', '/params/query', ['query' => ['required' => 'value']]),
            $client->request('GET', '/params/header', ['headers' => ['required' => 'value']]),
            $client->request('POST', '/params/body', ['form_params' => ['required' => 'value']]),
            $client->request('GET', '/params/cookie', ['cookies' => ['required' => 'value']]),
        ];

        foreach ($responses as $response) {
            expect($response->getStatusCode())->toBe(200)
                ->and(json_decode((string) $response->getBody(), true)['optional'])->toBe('default');
        }
    });

    it('hydrates a readonly body DTO and preserves constructor defaults', function () use ($client) {
        $response = $client->request('POST', '/params/body-dto', [
            'json' => [
                'email' => 'ada@example.com',
                'age' => '0',
                'active' => 'false',
                'status' => 'published',
                'nickname' => null,
            ],
        ]);

        expect($response->getStatusCode())->toBe(200)
            ->and(json_decode((string) $response->getBody(), true))->toMatchArray([
                'email' => 'ada@example.com',
                'age' => 0,
                'active' => false,
                'status' => 'published',
                'nickname' => null,
                'label' => 'default-label',
            ]);
    });

    it('hydrates a query DTO and converts scalar strings', function () use ($client) {
        $response = $client->request('GET', '/params/query-dto', [
            'query' => [
                'term' => 'framework',
                'page' => '2',
                'archived' => 'false',
                'status' => 'published',
            ],
        ]);

        expect($response->getStatusCode())->toBe(200)
            ->and(json_decode((string) $response->getBody(), true))->toBe([
                'term' => 'framework',
                'page' => 2,
                'archived' => false,
                'status' => 'published',
            ]);
    });

    it('distinguishes explicit null from an omitted PHP default', function () use ($client) {
        $response = $client->request('POST', '/params/typed/12', [
            'query' => ['zero' => '0', 'enabled' => 'false'],
            'headers' => ['X-Count' => '4'],
            'cookies' => ['enabled' => 'false'],
            'json' => ['nullable' => null],
        ]);

        expect($response->getStatusCode())->toBe(200)
            ->and(json_decode((string) $response->getBody(), true))->toBe([
                'id' => 12,
                'zero' => 0,
                'enabled' => false,
                'count' => 4,
                'cookie_enabled' => false,
                'nullable' => null,
                'optional' => 7,
            ]);
    });

    it('rejects explicit null for a non-nullable field', function () use ($client) {
        $response = $client->request('POST', '/params/body-dto', [
            'json' => [
                'email' => null,
                'age' => 1,
                'active' => true,
                'status' => 'draft',
                'nickname' => null,
            ],
        ]);

        expectValidationError($response, 'body', 'email', 'not_nullable');
    });

    it('returns a structured error for missing required values', function () use ($client) {
        $response = $client->request('GET', '/params/query');

        expectValidationError($response, 'query', 'required', 'required');
    });

    it('returns 422 for invalid scalars and backed enums', function () use ($client) {
        $scalar = $client->request('GET', '/params/query-dto', [
            'query' => ['term' => 'framework', 'page' => 'many'],
        ]);
        $enum = $client->request('GET', '/params/query-dto', [
            'query' => ['term' => 'framework', 'page' => '1', 'status' => 'unknown'],
        ]);

        expectValidationError($scalar, 'query', 'page', 'invalid_integer');
        expectValidationError($enum, 'query', 'status', 'invalid_enum');
    });

    it('rejects unknown DTO fields', function () use ($client) {
        $response = $client->request('GET', '/params/query-dto', [
            'query' => ['term' => 'framework', 'page' => '1', 'surprise' => 'value'],
        ]);

        expectValidationError($response, 'query', 'surprise', 'unknown_field');
    });

    it('aggregates conversion, field-rule, missing, and unknown-field violations', function () use ($client) {
        $response = $client->request('POST', '/params/body-dto', [
            'json' => [
                'email' => 'not-an-email',
                'age' => -1,
                'active' => 'sometimes',
                'status' => 'unknown',
                'surprise' => true,
            ],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(422)
            ->and($body['error']['message'])->toBe('Validation failed')
            ->and($body['error']['violations'])->toHaveCount(6)
            ->and(array_column($body['error']['violations'], 'code'))->toEqualCanonicalizing([
                'invalid_email',
                'min_value',
                'invalid_boolean',
                'invalid_enum',
                'required',
                'unknown_field',
            ]);
    });

    it('applies repeatable parameter validation and aggregates failures', function () use ($client) {
        $response = $client->request('GET', '/params/validated', [
            'query' => ['email' => 'invalid', 'score' => '-3'],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(422)
            ->and(array_column($body['error']['violations'], 'code'))->toBe([
                'invalid_email',
                'min_value',
            ]);
    });

    it('maps the controller validation helper to the same structured exception', function () use ($client) {
        $response = $client->request('GET', '/params/manual-validation', [
            'query' => ['email' => 'invalid', 'score' => '3'],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(422)
            ->and(array_column($body['error']['violations'], 'path'))->toBe(['email', 'score']);
    });

    it('parses raw JSON and vendor JSON media types while preserving the stream position', function () {
        $stream = Stream::create((string) json_encode([
            'email' => 'ada@example.com',
            'age' => 2,
            'active' => true,
            'status' => 'draft',
            'nickname' => null,
        ]));
        $stream->seek(7);
        $request = new ServerRequest(
            'POST',
            '/params/body-dto',
            ['Content-Type' => 'application/vnd.gustav+json; charset=utf-8'],
            $stream,
        );

        $response = createApplication()->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($body['stream_position'])->toBe(7);
    });

    it('returns 400 for malformed JSON', function () use ($client) {
        $response = $client->request('POST', '/params/body-dto', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"email":',
        ]);
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(400)
            ->and($body)->toBe([
                'error' => [
                    'status' => 400,
                    'message' => 'Malformed JSON body',
                ],
            ]);
    });

    it('returns 415 when a required raw body has an unsupported media type', function () use ($client) {
        $response = $client->request('POST', '/params/body-dto', [
            'headers' => ['Content-Type' => 'text/plain'],
            'body' => 'plain input',
        ]);

        expect($response->getStatusCode())->toBe(415);
    });

    it('continues serving after invalid input', function () use ($client) {
        $failed = $client->request('POST', '/params/body-dto', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{',
        ]);
        $next = $client->request('GET', '/responses/plaintext');

        expect($failed->getStatusCode())->toBe(400)
            ->and($next->getStatusCode())->toBe(200)
            ->and((string) $next->getBody())->toBe('lorem ipsum');
    });
});

function expectValidationError(
    Psr\Http\Message\ResponseInterface $response,
    string $source,
    string $path,
    string $code,
): void {
    $body = json_decode((string) $response->getBody(), true);
    $violations = array_values(array_filter(
        $body['error']['violations'] ?? [],
        fn (array $violation): bool => $violation['source'] === $source
            && $violation['path'] === $path
            && $violation['code'] === $code,
    ));

    expect($response->getStatusCode())->toBe(422)
        ->and($body['error']['status'])->toBe(422)
        ->and($body['error']['message'])->toBe('Validation failed')
        ->and($violations)->toHaveCount(1)
        ->and($violations[0]['message'])->toBeString()->not->toBeEmpty();
}
