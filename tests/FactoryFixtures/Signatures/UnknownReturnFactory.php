<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class UnknownReturnFactory
{
    public function __invoke(): MissingProduct
    {
        return new MissingProduct();
    }
}
