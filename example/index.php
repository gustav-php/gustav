<?php


namespace TorstenDittmann\Example;

require_once __DIR__ . '/../vendor/autoload.php';

use TorstenDittmann\Gustav\Application;
use TorstenDittmann\Gustav\Configuration;

$configuration = new Configuration(
    routeNamespaces: [
        'TorstenDittmann\Example\Routes'
    ],
);

$app = new Application(configuration: $configuration);

$app->start();
