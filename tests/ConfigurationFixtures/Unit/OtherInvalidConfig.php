<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class OtherInvalidConfig
{
    public function __construct(
        #[Env('APP_OTHER')]
        public string $other,
    ) {
    }
}
