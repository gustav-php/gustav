<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Config\Environment;
use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use Nyholm\Psr7\ServerRequest;

function configuredApplication(Environment $environment): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ConfigurationFixtures\\ValidApplication',
        cache: sys_get_temp_dir(),
        environment: $environment,
    ));
}

it('discovers typed configuration and injects it without application bindings', function () {
    $response = configuredApplication(Environment::fromArray([
        'APP_NAME' => 'Configured Gustav',
        'APP_DEBUG' => 'false',
    ]))->handle(new ServerRequest('GET', '/configuration'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode((string) $response->getBody(), true))->toBe([
            'name' => 'Configured Gustav',
            'debug' => false,
            'port' => 8080,
        ]);
});

it('fails application startup when required configuration is missing', function () {
    configuredApplication(Environment::fromArray(['APP_DEBUG' => 'true']));
})->throws(ConfigurationException::class, 'APP_NAME');

it('shares one immutable configuration instance across requests', function () {
    $application = configuredApplication(Environment::fromArray([
        'APP_NAME' => 'Configured Gustav',
        'APP_DEBUG' => 'true',
    ]));

    $first = $application->handle(new ServerRequest('GET', '/configuration/identity'));
    $second = $application->handle(new ServerRequest('GET', '/configuration/identity'));

    expect((string) $first->getBody())->toBe((string) $second->getBody());
});
