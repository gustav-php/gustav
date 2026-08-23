<?php

namespace GustavPHP\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use GustavPHP\Gustav\{Application, Configuration, Mode};

Application::run(new Configuration(
    mode: Mode::Production,
    namespace: __NAMESPACE__,
    files: __DIR__ . '/public/',
    views: __DIR__ . '/views/',
));
