<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Config;

use GustavPHP\Gustav\Attribute\{Config, Env};

#[Config]
final readonly class FactorySettings
{
    public function __construct(
        #[Env('FACTORY_PREFIX')]
        public string $prefix,
    ) {
    }
}
