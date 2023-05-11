<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Message\DriverInterface;
use GustavPHP\Gustav\Message\SAPI;

class Configuration
{
    public function __construct(
        public readonly string $mode = 'development',
        public readonly DriverInterface $driver = new SAPI\Driver(),
        public readonly array $routeNamespaces = [],
        public readonly ?string $files = null
    ) {
    }
}
