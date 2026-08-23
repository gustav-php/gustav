<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class CompleteConfig
{
    /**
     * @param array<mixed> $hosts
     */
    public function __construct(
        #[Env('APP_NAME')]
        public string $name,
        #[Env('APP_PORT')]
        public int $port,
        #[Env('APP_RATIO')]
        public float $ratio,
        #[Env('APP_ENABLED')]
        public bool $enabled,
        #[Env('APP_HOSTS')]
        public array $hosts,
        #[Env('APP_STAGE')]
        public Stage $stage,
        #[Env('APP_PRIORITY')]
        public Priority $priority,
        #[Env('APP_NOTE')]
        public ?string $note = null,
        #[Env('APP_TIMEOUT')]
        public int $timeout = 30,
    ) {
    }
}
