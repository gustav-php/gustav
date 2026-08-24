<?php

use GustavPHP\Gustav\Session;
use GustavPHP\Gustav\Session\SessionOptions;
use GustavPHP\Tests\SessionFixtures\MemorySessionStore;
use Nyholm\Psr7\{Response, ServerRequest};
use Psr\Http\Message\ResponseInterface;

function sessionCookieId(ResponseInterface $response, string $name = 'gustav_session'): string
{
    $cookie = $response->getHeaderLine('Set-Cookie');
    expect($cookie)->toContain("{$name}=");
    preg_match('/(?:^|;\s*)' . preg_quote($name, '/') . '=([^;]*)/', $cookie, $matches);

    return $matches[1] ?? '';
}

function requestWithSession(string $id, string $uri = 'http://example.test/'): ServerRequest
{
    return (new ServerRequest('GET', $uri))->withCookieParams(['gustav_session' => $id]);
}

it('does not create storage or a cookie until session state is used', function () {
    $store = new MemorySessionStore();
    $session = new Session($store, new SessionOptions(), new ServerRequest('GET', '/'));
    $response = $session->complete(new Response());

    expect($store->acquisitions)->toBe(0)
        ->and($store->ids())->toBe([])
        ->and($response->hasHeader('Set-Cookie'))->toBeFalse();
});

it('persists JSON-compatible values including explicit null', function () {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->put('profile', ['name' => 'Ada', 'active' => false, 'score' => 0.0]);
    $first->put('nullable', null);
    $id = sessionCookieId($first->complete(new Response()));

    $second = new Session($store, $options, requestWithSession($id));

    expect($second->get('profile'))->toBe(['name' => 'Ada', 'active' => false, 'score' => 0.0])
        ->and($second->has('nullable'))->toBeTrue()
        ->and($second->get('nullable', 'fallback'))->toBeNull();

    $second->complete(new Response());
});

it('expires flash data after the next successful request', function () {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->flash('notice', null);
    $id = sessionCookieId($first->complete(new Response()));

    $second = new Session($store, $options, requestWithSession($id));
    expect($second->hasFlash('notice'))->toBeTrue()
        ->and($second->getFlash('notice', 'fallback'))->toBeNull();
    $second->complete(new Response());

    $third = new Session($store, $options, requestWithSession($id));
    expect($third->hasFlash('notice'))->toBeFalse();
    $third->complete(new Response());
});

it('can keep flash data for another request', function () {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->flash('notice', 'saved');
    $id = sessionCookieId($first->complete(new Response()));

    $second = new Session($store, $options, requestWithSession($id));
    $second->keepFlash('notice');
    $second->complete(new Response());

    $third = new Session($store, $options, requestWithSession($id));
    expect($third->pullFlash('notice'))->toBe('saved')
        ->and($third->hasFlash('notice'))->toBeFalse();
    $third->complete(new Response());
});

it('regenerates identifiers without losing session state', function () {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->put('user_id', 42);
    $oldId = sessionCookieId($first->complete(new Response()));

    $second = new Session($store, $options, requestWithSession($oldId));
    $second->regenerate();
    $newId = sessionCookieId($second->complete(new Response()));

    expect($newId)->not->toBe($oldId)
        ->and($store->acquire($oldId))->toBeNull();
    $restored = new Session($store, $options, requestWithSession($newId));
    expect($restored->get('user_id'))->toBe(42);
    $restored->complete(new Response());
});

it('invalidates storage and expires the browser cookie', function () {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->put('user_id', 42);
    $id = sessionCookieId($first->complete(new Response()));

    $second = new Session($store, $options, requestWithSession($id));
    $second->invalidate();
    $response = $second->complete(new Response());

    expect($store->acquire($id))->toBeNull()
        ->and($response->getHeaderLine('Set-Cookie'))->toContain('gustav_session=')
        ->toContain('Max-Age=0');
});

it('does not commit mutations when a request fails', function (int $status) {
    $store = new MemorySessionStore();
    $options = new SessionOptions();
    $first = new Session($store, $options, new ServerRequest('GET', '/'));
    $first->put('value', 'before');
    $id = sessionCookieId($first->complete(new Response()));

    $failed = new Session($store, $options, requestWithSession($id));
    $failed->put('value', 'after');
    if ($status === 0) {
        $failed->abort();
    } else {
        $failed->complete(new Response($status));
    }

    $next = new Session($store, $options, requestWithSession($id));
    expect($next->get('value'))->toBe('before');
    $next->complete(new Response());
})->with(['exception' => [0], 'server error' => [500]]);

it('uses secure cookie attributes and automatically detects HTTPS', function () {
    $store = new MemorySessionStore();
    $session = new Session(
        $store,
        new SessionOptions(cookieName: 'session', lifetime: 60),
        new ServerRequest('GET', 'https://example.test/'),
    );
    $session->put('key', 'value');
    $cookie = $session->complete(new Response())->getHeaderLine('Set-Cookie');

    expect($cookie)->toContain('Secure')
        ->toContain('HttpOnly')
        ->toContain('SameSite=Lax')
        ->toContain('Max-Age=60');
});

it('clears malformed identifiers without touching storage', function () {
    $store = new MemorySessionStore();
    $session = new Session($store, new SessionOptions(), requestWithSession('not-valid'));
    $response = $session->complete(new Response());

    expect($store->acquisitions)->toBe(0)
        ->and($response->getHeaderLine('Set-Cookie'))->toContain('Max-Age=0');
});

it('clears an unrecognized identifier when session state is read', function () {
    $store = new MemorySessionStore();
    $session = new Session($store, new SessionOptions(), requestWithSession(str_repeat('a', 43)));
    expect($session->get('missing'))->toBeNull();
    $response = $session->complete(new Response());

    expect($store->acquisitions)->toBe(1)
        ->and($response->getHeaderLine('Set-Cookie'))->toContain('Max-Age=0');
});

it('rejects invalid keys and non-JSON-compatible values', function (string $key, mixed $value) {
    $session = new Session(
        new MemorySessionStore(),
        new SessionOptions(),
        new ServerRequest('GET', '/'),
    );

    expect(fn () => $session->put($key, $value))->toThrow(InvalidArgumentException::class);
})->with([
    'invalid key' => ['invalid key', 'value'],
    'object' => ['value', new stdClass()],
    'infinite float' => ['value', INF],
    'invalid utf8' => ['value', "\xFF"],
    'invalid nested key' => ['value', ["\xFF" => 'invalid']],
]);
