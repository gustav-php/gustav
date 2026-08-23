<?php

use GustavPHP\Gustav\CLI\ProjectBootstrap;
use GustavPHP\Gustav\Configuration;

it('loads a project configuration from the conventional bootstrap file', function () {
    $configuration = ProjectBootstrap::load(
        dirname(__DIR__, 2) . '/CommandFixtures/Project',
    );

    expect($configuration)->toBeInstanceOf(Configuration::class)
        ->and($configuration?->namespace)
        ->toBe('GustavPHP\\Tests\\CommandFixtures\\ValidApplication');
});

it('allows framework tooling to run outside an application project', function () {
    expect(ProjectBootstrap::load(dirname(__DIR__, 2) . '/CommandFixtures/MissingProject'))
        ->toBeNull();
});

it('rejects project bootstrap files that do not return configuration', function () {
    ProjectBootstrap::load(dirname(__DIR__, 2) . '/CommandFixtures/InvalidProject');
})->throws(LogicException::class, Configuration::class);
