<?php

namespace GustavPHP\Tests\FactoryFixtures\DuplicateApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\DuplicateApplication\Products\DuplicateProduct;

#[Factory]
final class FirstFactory
{
    public function __invoke(): DuplicateProduct
    {
        return new DuplicateProduct('first');
    }
}
