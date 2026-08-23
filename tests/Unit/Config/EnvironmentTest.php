<?php

use GustavPHP\Gustav\Config\{Environment, Violation};
use GustavPHP\Gustav\Config\Exception\ConfigurationException;

function temporaryEnvironmentDirectory(): string
{
    $directory = sys_get_temp_dir() . '/gustav-environment-' . bin2hex(random_bytes(8));
    mkdir($directory, recursive: true);

    return $directory;
}

function removeEnvironmentDirectory(string $directory): void
{
    foreach (['.env.local', '.env'] as $file) {
        $path = $directory . '/' . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }
    rmdir($directory);
}

it('loads local dotenv values while preserving real environment variables', function () {
    $directory = temporaryEnvironmentDirectory();
    file_put_contents($directory . '/.env', "APP_NAME=base\nAPP_REGION=base\n");
    file_put_contents($directory . '/.env.local', "APP_NAME=local\nAPP_REGION=local\n");

    try {
        $environment = Environment::load($directory, system: ['APP_NAME' => 'system']);

        expect($environment->get('APP_NAME'))->toBe('system')
            ->and($environment->get('APP_REGION'))->toBe('local')
            ->and($environment->has('MISSING'))->toBeFalse();
    } finally {
        removeEnvironmentDirectory($directory);
    }
});

it('treats dotenv files as optional', function () {
    $directory = temporaryEnvironmentDirectory();

    try {
        expect(Environment::load($directory, system: [])->has('ANYTHING'))->toBeFalse();
    } finally {
        removeEnvironmentDirectory($directory);
    }
});

it('reports malformed dotenv files without exposing their contents', function () {
    $directory = temporaryEnvironmentDirectory();
    file_put_contents($directory . '/.env', "APP_TOKEN=\"super-secret-value\n");

    try {
        Environment::load($directory, system: []);
    } catch (ConfigurationException $exception) {
        expect($exception->getMessage())->toContain('.env')
            ->not->toContain('super-secret-value')
            ->and($exception->getViolations())->toHaveCount(1)
            ->and($exception->getViolations()[0])->toEqual(new Violation(
                configuration: Environment::class,
                property: 'file',
                variable: '.env',
                code: 'invalid_environment_file',
                message: 'Environment file could not be parsed',
            ));

        return;
    } finally {
        removeEnvironmentDirectory($directory);
    }

    throw new RuntimeException('Expected malformed environment file to fail');
});
