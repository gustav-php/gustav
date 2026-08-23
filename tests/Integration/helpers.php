<?php

namespace GustavPHP\Tests\Integration;

use GustavPHP\Gustav\{Application, Configuration, Mode};

function createApplication(Mode $mode = Mode::Production): Application
{
    return new Application(new Configuration(
        mode: $mode,
        namespace: __NAMESPACE__,
        views: __DIR__ . '/views/',
        serviceNamespaces: ['GustavPHP\\Tests\\Fixtures\\QuietLogging\\Services'],
    ));
}

function createClient(): Client
{
    return new Client();
}
