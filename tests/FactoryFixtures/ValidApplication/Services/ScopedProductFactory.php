<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\ScopedProduct;

#[Factory]
final class ScopedProductFactory
{
    public static int $calls = 0;

    public function __invoke(): ScopedProduct
    {
        return new ScopedProduct(++self::$calls);
    }
}
