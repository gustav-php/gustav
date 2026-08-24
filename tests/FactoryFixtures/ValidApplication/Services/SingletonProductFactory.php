<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\SingletonProduct;

#[Factory(Lifetime::Singleton)]
final class SingletonProductFactory
{
    public static int $calls = 0;

    public function __invoke(): SingletonProduct
    {
        return new SingletonProduct(++self::$calls);
    }
}
