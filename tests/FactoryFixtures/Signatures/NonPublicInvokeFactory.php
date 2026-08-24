<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class NonPublicInvokeFactory
{
    private function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
