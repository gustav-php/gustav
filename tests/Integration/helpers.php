<?php

namespace GustavPHP\Tests\Integration;

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Auth\Authenticator;
use GustavPHP\Tests\Integration\Auth\HeaderAuthenticator;
use GustavPHP\Tests\Integration\Services\{DefaultGreeting, Greeting, RequestState, SingletonState, TransientState};

function createApplication(Mode $mode = Mode::Production): Application
{
    $application = new Application(new Configuration(
        mode: $mode,
        namespace: __NAMESPACE__,
        cache: __DIR__ . '/cache/',
    ));

    $application->services()->bind(
        Authenticator::class,
        HeaderAuthenticator::class,
    )->bind(
        Greeting::class,
        DefaultGreeting::class,
    )->request(
        RequestState::class,
    )->singleton(
        SingletonState::class,
    )->transient(
        TransientState::class,
    );

    return $application;
}

function createClient(): Client
{
    return new Client();
}
