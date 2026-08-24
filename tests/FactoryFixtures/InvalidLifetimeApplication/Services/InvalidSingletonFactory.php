<?php

namespace GustavPHP\Tests\FactoryFixtures\InvalidLifetimeApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Tests\FactoryFixtures\InvalidLifetimeApplication\Products\InvalidSingletonProduct;

#[Factory(Lifetime::Singleton)]
final readonly class InvalidSingletonFactory
{
    public function __construct(private ScopedDependency $dependency)
    {
    }

    public function __invoke(): InvalidSingletonProduct
    {
        return new InvalidSingletonProduct($this->dependency->value());
    }
}
