<?php


namespace GustavPHP\Demo;

require_once __DIR__ . '/../vendor/autoload.php';

use GustavPHP\Gustav\Application;
use GustavPHP\Gustav\Configuration;
use GustavPHP\Gustav\Mode;

$configuration = new Configuration(
    mode: Mode::Development,
    namespace: __NAMESPACE__,
    cache: __DIR__ . '/cache/',
    files: __DIR__ . '/public/'
);

$app = new Application(configuration: $configuration);

$app->start();
