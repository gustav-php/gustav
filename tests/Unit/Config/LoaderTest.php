<?php

use GustavPHP\Gustav\Config\{Environment, Loader};
use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use GustavPHP\Tests\ConfigurationFixtures\Unit\{AmbiguousConfig, CompleteConfig, InvalidScalarConfig, MissingAttributeConfig, MultipleInvalidConfig, MutableConfig, OtherInvalidConfig, Priority, RequiredNullableConfig, Stage};

it('hydrates every supported configuration type and preserves constructor defaults', function () {
    $configurations = (new Loader(Environment::fromArray([
        'APP_NAME' => 'Gustav',
        'APP_PORT' => '0',
        'APP_RATIO' => '-0.5',
        'APP_ENABLED' => 'false',
        'APP_HOSTS' => '["api.internal","worker.internal"]',
        'APP_STAGE' => 'production',
        'APP_PRIORITY' => '2',
    ])))->load([CompleteConfig::class]);

    expect($configurations[CompleteConfig::class])->toEqual(new CompleteConfig(
        name: 'Gustav',
        port: 0,
        ratio: -0.5,
        enabled: false,
        hosts: ['api.internal', 'worker.internal'],
        stage: Stage::Production,
        priority: Priority::High,
    ));
});

it('reports deterministic violations for every failed conversion', function () {
    try {
        (new Loader(Environment::fromArray([
            'APP_INTEGER' => '1.5',
            'APP_DECIMAL' => 'infinite',
            'APP_BOOLEAN' => 'yes',
            'APP_ITEMS' => 'not-json',
            'APP_PRIORITY' => '3',
        ])))->load([InvalidScalarConfig::class]);
    } catch (ConfigurationException $exception) {
        expect(array_column(array_map(
            fn ($violation): array => $violation->toArray(),
            $exception->getViolations(),
        ), 'code'))->toBe([
            'invalid_integer',
            'invalid_decimal',
            'invalid_boolean',
            'invalid_array',
            'invalid_enum',
        ]);

        return;
    }

    throw new RuntimeException('Expected invalid scalar configuration to fail');
});

it('does not make a nullable constructor parameter optional without a PHP default', function () {
    (new Loader(Environment::fromArray([])))->load([RequiredNullableConfig::class]);
})->throws(ConfigurationException::class, 'APP_NOTE');

it('aggregates missing, conversion, enum, and validation failures', function () {
    try {
        (new Loader(Environment::fromArray([
            'APP_PORT' => '0',
            'APP_EMAIL' => 'super-secret-invalid-email',
            'APP_STAGE' => 'super-secret-invalid-stage',
        ])))->load([MultipleInvalidConfig::class, OtherInvalidConfig::class]);
    } catch (ConfigurationException $exception) {
        expect($exception->getViolations())->toHaveCount(5)
            ->and(array_column(array_map(
                fn ($violation): array => $violation->toArray(),
                $exception->getViolations(),
            ), 'code'))->toBe([
                'min_value',
                'invalid_email',
                'invalid_enum',
                'required',
                'required',
            ])
            ->and($exception->getMessage())->toContain('APP_PORT')
            ->toContain('APP_EMAIL')
            ->toContain('APP_STAGE')
            ->toContain('APP_TOKEN')
            ->toContain('APP_OTHER')
            ->not->toContain('super-secret-invalid-email')
            ->not->toContain('super-secret-invalid-stage');

        return;
    }

    throw new RuntimeException('Expected invalid configuration to fail');
});

it('rejects ambiguous configuration types during startup compilation', function () {
    (new Loader(Environment::fromArray(['APP_VALUE' => 'value'])))
        ->load([AmbiguousConfig::class]);
})->throws(LogicException::class, 'ambiguous unions are not supported');

it('requires immutable configuration classes', function () {
    (new Loader(Environment::fromArray(['APP_VALUE' => 'value'])))
        ->load([MutableConfig::class]);
})->throws(LogicException::class, 'must be readonly');

it('requires an environment mapping for every constructor parameter', function () {
    (new Loader(Environment::fromArray(['APP_VALUE' => 'value'])))
        ->load([MissingAttributeConfig::class]);
})->throws(LogicException::class, 'must declare exactly one');
