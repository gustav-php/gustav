<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class BuiltinReturnFactory
{
    public function __invoke(): string
    {
        return 'product';
    }
}
