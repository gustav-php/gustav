<?php

use GustavPHP\Gustav\Config\Environment;
use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use GustavPHP\Gustav\{Configuration, Mode};

it('builds conventional project paths and mode from a captured environment', function () {
    $configuration = Configuration::forProject(
        namespace: 'App',
        root: '/srv/example',
        environment: Environment::fromArray(['MODE' => 'production']),
        configurationNamespaces: ['Module\\Billing\\Config'],
        commandNamespaces: ['Module\\Billing\\Commands'],
    );

    expect($configuration->mode)->toBe(Mode::Production)
        ->and($configuration->namespace)->toBe('App')
        ->and($configuration->files)->toBe('/srv/example/public/')
        ->and($configuration->views)->toBe('/srv/example/views/')
        ->and($configuration->configurationNamespaces)->toBe(['Module\\Billing\\Config'])
        ->and($configuration->commandNamespaces)->toBe(['Module\\Billing\\Commands']);
});

it('defaults conventional projects to development mode', function () {
    expect(Configuration::forProject(
        namespace: 'App',
        root: '/srv/example/',
        environment: Environment::fromArray([]),
    )->mode)->toBe(Mode::Development);
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
