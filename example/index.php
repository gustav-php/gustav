<?php


namespace TorstenDittmann\Example;

require_once __DIR__ . '/../vendor/autoload.php';

use TorstenDittmann\Example\Routes\CatsController;
use TorstenDittmann\Gustav\Application;


$app = new Application(routes: [CatsController::class]);

$app->start();
