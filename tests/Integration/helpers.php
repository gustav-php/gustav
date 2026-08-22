<?php

namespace GustavPHP\Tests\Integration;

use GustavPHP\Gustav\{Application, Configuration, Mode};

function createApplication(Mode $mode = Mode::Production): Application
{
    return new Application(new Configuration(
        mode: $mode,
        namespace: __NAMESPACE__,
        cache: __DIR__ . '/cache/',
    ));
}

function createClient(): Client
{
    return new Client();
}
