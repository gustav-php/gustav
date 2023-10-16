<?php

namespace GustavPHP\Gustav;

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
        public readonly string $host = '0.0.0.0',
        public readonly int $port = 4201,
        public readonly array $routeNamespaces = [],
        public readonly array $eventNamespaces = [],
        public readonly ?string $files = null
    ) {
    }
}
