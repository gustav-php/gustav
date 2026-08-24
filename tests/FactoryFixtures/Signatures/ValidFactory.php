<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;

#[Factory(Lifetime::Transient)]
final class ValidFactory
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
