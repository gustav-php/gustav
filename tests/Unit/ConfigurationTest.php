<?php

use GustavPHP\Gustav\Config\Environment;
use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use GustavPHP\Gustav\{Configuration, Mode};
use GustavPHP\Gustav\Session\SessionOptions;

it('builds conventional project paths and mode from a captured environment', function () {
    $configuration = Configuration::forProject(
        namespace: 'App',
        root: '/srv/example',
        environment: Environment::fromArray(['MODE' => 'production']),
        configurationNamespaces: ['Module\\Billing\\Config'],
        commandNamespaces: ['Module\\Billing\\Commands'],
        exceptionHandlerNamespaces: ['Module\\Billing\\ExceptionHandlers'],
    );

    expect($configuration->mode)->toBe(Mode::Production)
        ->and($configuration->namespace)->toBe('App')
        ->and($configuration->files)->toBe('/srv/example/public/')
        ->and($configuration->views)->toBe('/srv/example/views/')
        ->and($configuration->session?->directory)->toBe('/srv/example/storage/sessions/')
        ->and($configuration->configurationNamespaces)->toBe(['Module\\Billing\\Config'])
        ->and($configuration->commandNamespaces)->toBe(['Module\\Billing\\Commands'])
        ->and($configuration->exceptionHandlerNamespaces)->toBe(['Module\\Billing\\ExceptionHandlers']);
});

it('defaults conventional projects to development mode', function () {
    expect(Configuration::forProject(
        namespace: 'App',
        root: '/srv/example/',
        environment: Environment::fromArray([]),
    )->mode)->toBe(Mode::Development);
});

it('accepts custom conventional session options', function () {
    $session = new SessionOptions(directory: '/var/run/example-sessions', secure: true);

    expect(Configuration::forProject(
        namespace: 'App',
        root: '/srv/example/',
        environment: Environment::fromArray([]),
        session: $session,
    )->session)->toBe($session);
});

it('preserves the legacy positional constructor argument order', function () {
    $environment = Environment::fromArray(['MODE' => 'production']);
    $session = new SessionOptions(directory: '/var/run/legacy-sessions');

    $configuration = new Configuration(
        Mode::Production,
        'LegacyApp',
        '/srv/legacy/public',
        '/srv/legacy/views',
        '127.0.0.1',
        8080,
        ['Legacy\\Routes'],
        ['Legacy\\Events'],
        ['Legacy\\Serializers'],
        ['Legacy\\Services'],
        ['Legacy\\Middleware'],
        ['Legacy\\Config'],
        ['Legacy\\Commands'],
        $environment,
        $session,
    );

    expect($configuration->session)->toBe($session)
        ->and($configuration->getEnvironment())->toBe($environment)
        ->and($configuration->commandNamespaces)->toBe(['Legacy\\Commands'])
        ->and($configuration->exceptionHandlerNamespaces)->toBe([]);
});

it('preserves the legacy positional forProject argument order', function () {
    $environment = Environment::fromArray(['MODE' => 'production']);
    $session = new SessionOptions(directory: '/var/run/legacy-project-sessions');

    $configuration = Configuration::forProject(
        'LegacyApp',
        '/srv/legacy-project',
        $environment,
        ['Legacy\\Routes'],
        ['Legacy\\Events'],
        ['Legacy\\Serializers'],
        ['Legacy\\Services'],
        ['Legacy\\Middleware'],
        ['Legacy\\Config'],
        ['Legacy\\Commands'],
        $session,
    );

    expect($configuration->mode)->toBe(Mode::Production)
        ->and($configuration->session)->toBe($session)
        ->and($configuration->commandNamespaces)->toBe(['Legacy\\Commands'])
        ->and($configuration->exceptionHandlerNamespaces)->toBe([]);
});

it('rejects invalid modes without exposing the supplied value', function () {
    try {
        Configuration::forProject(
            namespace: 'App',
            root: '/srv/example',
            environment: Environment::fromArray(['MODE' => 'super-secret-mode']),
        );
    } catch (ConfigurationException $exception) {
        expect($exception->getMessage())->toContain('MODE')
            ->not->toContain('super-secret-mode');

        return;
    }

    throw new RuntimeException('Expected invalid mode to fail');
});
