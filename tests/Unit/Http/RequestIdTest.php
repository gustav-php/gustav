<?php

use GustavPHP\Gustav\Http\RequestId;
use Nyholm\Psr7\ServerRequest;

it('generates a request id when the header is missing', function () {
    $requestId = RequestId::fromRequest(new ServerRequest('GET', '/'));

    expect((string) $requestId)->toMatch('/^[a-f0-9]{32}$/');
});

it('preserves a safe incoming request id', function () {
    $requestId = RequestId::fromRequest(
        new ServerRequest('GET', '/', ['X-Request-ID' => 'gateway.request-123']),
    );

    expect((string) $requestId)->toBe('gateway.request-123');
});

it('replaces unsafe incoming request ids', function (string $value) {
    $requestId = RequestId::fromRequest(
        new ServerRequest('GET', '/', ['X-Request-ID' => $value]),
    );

    expect((string) $requestId)->toMatch('/^[a-f0-9]{32}$/')
        ->not->toBe($value);
})->with([
    'empty' => '',
    'whitespace' => 'unsafe request id',
    'multiple values' => 'first,second',
    'leading punctuation' => '.request-id',
    'non-ASCII' => 'réquest-id',
    'oversized' => str_repeat('a', 129),
]);

it('rejects unsafe explicitly constructed request ids', function () {
    RequestId::fromString('unsafe request id');
})->throws(InvalidArgumentException::class);

it('replaces repeated request id headers', function () {
    $requestId = RequestId::fromRequest(new ServerRequest(
        'GET',
        '/',
        ['X-Request-ID' => ['first', 'second']],
    ));

    expect((string) $requestId)->toMatch('/^[a-f0-9]{32}$/')
        ->not->toBe('first')
        ->not->toBe('second');
});
