<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\Config;

#[Config]
final readonly class MissingAttributeConfig
{
    public function __construct(public string $value)
    {
    }
}
