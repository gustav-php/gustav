<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
#[Factory]
final class DuplicateAttributeFactory
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
