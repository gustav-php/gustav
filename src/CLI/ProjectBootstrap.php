<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Configuration;
use LogicException;

final readonly class ProjectBootstrap
{
    public static function load(?string $root = null): ?Configuration
    {
        $root ??= getcwd() ?: '.';
        $path = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($path)) {
            return null;
        }

        $configuration = require $path;
        if (!$configuration instanceof Configuration) {
            throw new LogicException("Project bootstrap '{$path}' must return " . Configuration::class);
        }

        return $configuration;
    }
}
