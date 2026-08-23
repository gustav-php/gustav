<?php

namespace GustavPHP\Tests\ConfigurationFixtures\ValidApplication\Config;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class ApplicationSettings
{
    public function __construct(
        #[Env('APP_NAME')]
        public string $name,
        #[Env('APP_DEBUG')]
        public bool $debug,
        #[Env('APP_PORT')]
        public int $port = 8080,
    ) {
    }
}
