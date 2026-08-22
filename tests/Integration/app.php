<?php

namespace GustavPHP\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Auth\Authenticator;
use GustavPHP\Tests\Integration\Auth\HeaderAuthenticator;
use GustavPHP\Tests\Integration\Services\{DefaultGreeting, Greeting, RequestState, SingletonState, TransientState};

$configuration = new Configuration(
    mode: Mode::Development,
    namespace: __NAMESPACE__,
    cache: __DIR__ . '/cache/',
    files: __DIR__ . '/public/'
);

$app = new Application(configuration: $configuration);
$app->services()
    ->bind(Authenticator::class, HeaderAuthenticator::class)
    ->bind(Greeting::class, DefaultGreeting::class)
    ->request(RequestState::class)
    ->singleton(SingletonState::class)
    ->transient(TransientState::class);

$app->start();
