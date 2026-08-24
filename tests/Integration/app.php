<?php

namespace GustavPHP\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Session\SessionOptions;

Application::run(new Configuration(
    mode: Mode::Production,
    namespace: __NAMESPACE__,
    files: __DIR__ . '/public/',
    views: __DIR__ . '/views/',
    session: new SessionOptions(directory: integrationSessionDirectory()),
));
