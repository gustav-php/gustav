<?php

namespace GustavPHP\Tests\FactoryFixtures\ProviderApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\ProviderApplication\Products\{FactoryProduct, ProviderContract};

#[Factory]
final class ProductFactory
{
    public function __invoke(): ProviderContract
    {
        return new FactoryProduct();
    }
}
