<?php

namespace GustavPHP\Gustav;

readonly class Configuration
{
    public function __construct(
        /**
         * Application mode.
         *
         * @var Mode
         */
        public Mode $mode,
        /**
         * Namespace containing application classes.
         */
        public string $namespace,
        /**
         * Path to cache directory.
         *
         * @var string
         */
        public string $cache,
        /**
         * Path to the directory containing static files to serve.
         *
         * @var null|string
         */
        public ?string $files = null,
        /**
         * Hostname or IP address to listen on.
         *
         * @var string
         */
        public string $host = '0.0.0.0',
        /**
         * Port to listen on.
         *
         * @var int
         */
        public int $port = 4201,

        /**
         * Namespace containing additional routes.
         *
         * @var array<string>
         */
        public array $routeNamespaces = [],
        /**
         * Namespace containing additional event listeners.
         *
         * @var array<string>
         */
        public array $eventNamespaces = [],
        /**
         * Namespace containing additional serializers.
         *
         * @var array<string>
         */
        public array $serializerNamespaces = [],
    ) {
    }
}
