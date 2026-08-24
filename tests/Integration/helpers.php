<?php

namespace GustavPHP\Tests\Integration;

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Session\SessionOptions;

function integrationSessionDirectory(): string
{
    static $directory;
    if (is_string($directory)) {
        return $directory;
    }
    $directory = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'gustav-integration-sessions-'
        . getmypid()
        . '-'
        . bin2hex(random_bytes(4));
    register_shutdown_function(function () use ($directory): void {
        if (!is_dir($directory)) {
            return;
        }
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
        @rmdir($directory);
    });

    return $directory;
}

function createApplication(Mode $mode = Mode::Production): Application
{
    return new Application(new Configuration(
        mode: $mode,
        namespace: __NAMESPACE__,
        views: __DIR__ . '/views/',
        serviceNamespaces: ['GustavPHP\\Tests\\Fixtures\\QuietLogging\\Services'],
        session: new SessionOptions(directory: integrationSessionDirectory()),
    ));
}

function createClient(): Client
{
    return new Client();
}
