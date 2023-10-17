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
         */
        public readonly string $cache,
        /**
         * Application mode.
         */
        public readonly Mode $mode,
        /**
         * Hostname or IP address to listen on.
         */
        public readonly string $host = '0.0.0.0',
        /**
         * Port to listen on.
         */
        public readonly int $port = 4201,
        /**
         * Namespace containing the routes.
         */
        public readonly array $routeNamespaces = [],
        /**
         * Namespace containing the event listeners.
         */
        public readonly array $eventNamespaces = [],
        /**
         * Path to the directory containing static files to serve.
         */
        public readonly ?string $files = null
    ) {
    }
}
