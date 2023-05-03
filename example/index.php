<?php


namespace GustavPHP\Example;

require_once __DIR__ . '/../vendor/autoload.php';

use GustavPHP\Gustav\Application;
use GustavPHP\Gustav\Configuration;

$configuration = new Configuration(
    files: __DIR__ . '/public/',
    routeNamespaces: [
        'GustavPHP\Example\Routes'
    ]
);

$app = new Application(configuration: $configuration);

$app->start();
