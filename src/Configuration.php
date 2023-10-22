<?php

namespace GustavPHP\Gustav;

class Configuration
{
    public function __construct(
        /**
         * Namespace containing application classes.
         */
        public readonly string $namespace,
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
         * Namespace containing additional routes.
         *
         * @var array<string>
         */
        public readonly array $routeNamespaces = [],
        /**
         * Namespace containing additional event listeners.
         *
         * @var array<string>
         */
        public readonly array $eventNamespaces = [],
        /**
         * Namespace containing additional serializers.
         *
         * @var array<string>
         */
        public readonly array $serializerNamespaces = [],
        /**
         * Path to the directory containing static files to serve.
         *
         * @var null|string
         */
        public readonly ?string $files = null
    ) {
    }
}
