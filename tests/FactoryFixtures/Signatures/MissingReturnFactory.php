<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class MissingReturnFactory
{
    public function __invoke()
    {
        return new ProductImplementation();
    }
}
