<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class InvokeParameterFactory
{
    public function __invoke(string $value): Product
    {
        return new ProductImplementation();
    }
}
