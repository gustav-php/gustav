<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\TransientProduct;

#[Factory(Lifetime::Transient)]
final class TransientProductFactory
{
    public static int $calls = 0;

    public function __invoke(): TransientProduct
    {
        return new TransientProduct(++self::$calls);
    }
}
