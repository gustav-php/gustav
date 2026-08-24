<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
abstract class AbstractFactory
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
