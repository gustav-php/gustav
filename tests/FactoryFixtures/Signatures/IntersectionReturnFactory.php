<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class IntersectionReturnFactory
{
    public function __invoke(): Product&OtherProduct
    {
        return new ProductImplementation();
    }
}
