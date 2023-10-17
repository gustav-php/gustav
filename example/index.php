<?php


namespace GustavPHP\Example;

require_once __DIR__ . '/../vendor/autoload.php';

use GustavPHP\Gustav\Application;
use GustavPHP\Gustav\Configuration;
use GustavPHP\Gustav\Mode;

$configuration = new Configuration(
    mode: Mode::Development,
    cache: __DIR__ . '/cache/',
    files: __DIR__ . '/public/',
    eventNamespaces: [
        'GustavPHP\Example\Events'
    ],
    routeNamespaces: [
        'GustavPHP\Example\Routes'
    ],
);

$app = new Application(configuration: $configuration);

$app->start();
