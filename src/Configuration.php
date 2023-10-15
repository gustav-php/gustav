<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Message\DriverInterface;
use GustavPHP\Gustav\Message\SAPI;

enum Mode
{
    case Development;
    case Production;
}

class Configuration
{
    public function __construct(
        public readonly string $cache,
        public readonly Mode $mode,
        public readonly DriverInterface $driver = new SAPI\Driver(),
        public readonly array $routeNamespaces = [],
        public readonly array $eventNamespaces = [],
        public readonly ?string $files = null
    ) {
    }
}
