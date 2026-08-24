<?php

use GustavPHP\Gustav\Session\{SameSite, SessionOptions};

it('provides secure server-side session defaults', function () {
    $options = new SessionOptions(directory: '/tmp/sessions');

    expect($options->cookieName)->toBe('gustav_session')
        ->and($options->lifetime)->toBe(7200)
        ->and($options->cookiePath)->toBe('/')
        ->and($options->secure)->toBeNull()
        ->and($options->sameSite)->toBe(SameSite::Lax)
        ->and($options->garbageCollectionProbability)->toBe(1);
});

it('rejects unsafe cookie and storage options', function (array $arguments) {
    expect(fn () => new SessionOptions(...$arguments))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty directory' => [['directory' => '']],
    'invalid cookie name' => [['cookieName' => 'bad cookie']],
    'non-positive lifetime' => [['lifetime' => 0]],
    'relative cookie path' => [['cookiePath' => 'admin']],
    'cookie path injection' => [['cookiePath' => "/;\r\nInjected: yes"]],
    'domain injection' => [['cookieDomain' => "example.com\r\nInjected: yes"]],
    'insecure SameSite none' => [['sameSite' => SameSite::None]],
    'insecure secure prefix' => [['cookieName' => '__Secure-session']],
    'host prefix with domain' => [[
        'cookieName' => '__Host-session',
        'secure' => true,
        'cookieDomain' => 'example.com',
    ]],
    'invalid garbage collection chance' => [['garbageCollectionProbability' => 101]],
]);

it('accepts a correctly constrained host cookie', function () {
    $options = new SessionOptions(
        cookieName: '__Host-session',
        secure: true,
        sameSite: SameSite::Strict,
    );

    expect($options->cookiePath)->toBe('/')
        ->and($options->cookieDomain)->toBeNull();
});
