<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final class MutableConfig
{
    public function __construct(
        #[Env('APP_VALUE')]
        public string $value,
    ) {
    }
}
