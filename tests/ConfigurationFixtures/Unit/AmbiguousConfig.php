<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class AmbiguousConfig
{
    public function __construct(
        #[Env('APP_VALUE')]
        public string|int $value,
    ) {
    }
}
