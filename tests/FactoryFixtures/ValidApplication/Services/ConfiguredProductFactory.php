<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Config\FactorySettings;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\{ConfiguredProduct, FactoryContract};

#[Factory]
final readonly class ConfiguredProductFactory
{
    public function __construct(
        private FactorySettings $settings,
        private FactoryDependency $dependency,
    ) {
    }

    public function __invoke(): FactoryContract
    {
        return new ConfiguredProduct("{$this->settings->prefix}:{$this->dependency->value}");
    }
}
