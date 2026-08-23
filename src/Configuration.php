<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Config\{Environment, Violation};
use GustavPHP\Gustav\Config\Exception\ConfigurationException;

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
         * Path to the directory containing view templates..
         *
         * @var null|string
         */
        public ?string $views = null,
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
        /**
         * Namespace containing discoverable services.
         *
         * @var array<string>
         */
        public array $serviceNamespaces = [],
        /**
         * Namespace containing discoverable application-wide middleware.
         *
         * @var array<string>
         */
        public array $middlewareNamespaces = [],
        /**
         * Namespace containing discoverable typed configuration classes.
         *
         * @var array<string>
         */
        public array $configurationNamespaces = [],
        /**
         * Namespace containing discoverable application commands.
         *
         * @var array<string>
         */
        public array $commandNamespaces = [],
        /**
         * Captured environment used to hydrate typed application configuration.
         */
        private ?Environment $environment = null,
    ) {
    }

    /**
     * Create a configuration using the conventional project directories and
     * the MODE value from .env, .env.local, or the real process environment.
     *
     * @param array<string> $routeNamespaces
     * @param array<string> $eventNamespaces
     * @param array<string> $serializerNamespaces
     * @param array<string> $serviceNamespaces
     * @param array<string> $middlewareNamespaces
     * @param array<string> $configurationNamespaces
     * @param array<string> $commandNamespaces
     * @throws ConfigurationException
     */
    public static function forProject(
        string $namespace,
        string $root,
        ?Environment $environment = null,
        array $routeNamespaces = [],
        array $eventNamespaces = [],
        array $serializerNamespaces = [],
        array $serviceNamespaces = [],
        array $middlewareNamespaces = [],
        array $configurationNamespaces = [],
        array $commandNamespaces = [],
    ): self {
        $environment ??= Environment::load($root);
        $mode = match ($environment->get('MODE')) {
            null, 'development' => Mode::Development,
            'production' => Mode::Production,
            default => throw new ConfigurationException([
                new Violation(
                    configuration: self::class,
                    property: 'mode',
                    variable: 'MODE',
                    code: 'invalid_mode',
                    message: 'Value must be development or production',
                ),
            ]),
        };
        $root = rtrim($root, '/\\');
        $root = $root === '' ? DIRECTORY_SEPARATOR : $root . DIRECTORY_SEPARATOR;

        return new self(
            mode: $mode,
            namespace: $namespace,
            cache: $root . 'cache' . DIRECTORY_SEPARATOR,
            files: $root . 'public' . DIRECTORY_SEPARATOR,
            views: $root . 'views' . DIRECTORY_SEPARATOR,
            routeNamespaces: $routeNamespaces,
            eventNamespaces: $eventNamespaces,
            serializerNamespaces: $serializerNamespaces,
            serviceNamespaces: $serviceNamespaces,
            middlewareNamespaces: $middlewareNamespaces,
            configurationNamespaces: $configurationNamespaces,
            commandNamespaces: $commandNamespaces,
            environment: $environment,
        );
    }

    /** @internal */
    public function getEnvironment(): ?Environment
    {
        return $this->environment;
    }
}
