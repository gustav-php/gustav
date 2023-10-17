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
        /**
         * Path to cache directory.
         * 
         * @var string
         */
        public readonly string $cache,
        /**
         * Application mode.
         * 
         * @var Mode
         */
        public readonly Mode $mode,
        /**
         * Hostname or IP address to listen on.
         * 
         * @var string
         */
        public readonly string $host = '0.0.0.0',
        /**
         * Port to listen on.
         * 
         * @var int
         */
        public readonly int $port = 4201,
        /**
         * Namespace containing the routes.
         * 
         * @var array<string>
         */
        public readonly array $routeNamespaces = [],
        /**
         * Namespace containing the event listeners.
         * 
         * @var array<string>
         */
        public readonly array $eventNamespaces = [],
        /**
         * Namespace containing the services.
         * 
         * @var array<string>
         */
        public readonly array $serviceNamespaces = [],
        /**
         * Path to the directory containing static files to serve.
         * 
         * @var null|string
         */
        public readonly ?string $files = null
    ) {
    }
}
