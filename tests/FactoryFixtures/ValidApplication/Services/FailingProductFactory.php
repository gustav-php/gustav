<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\FailingProduct;
use RuntimeException;

#[Factory]
final class FailingProductFactory
{
    public static int $calls = 0;

    public function __invoke(): FailingProduct
    {
        self::$calls++;

        throw new RuntimeException('private factory failure');
    }
}
