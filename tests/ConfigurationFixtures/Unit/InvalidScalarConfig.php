<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class InvalidScalarConfig
{
    /** @param array<mixed> $items */
    public function __construct(
        #[Env('APP_INTEGER')]
        public int $integer,
        #[Env('APP_DECIMAL')]
        public float $decimal,
        #[Env('APP_BOOLEAN')]
        public bool $boolean,
        #[Env('APP_ITEMS')]
        public array $items,
        #[Env('APP_PRIORITY')]
        public Priority $priority,
    ) {
    }
}
