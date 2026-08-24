<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class NullableReturnFactory
{
    public function __invoke(): ?Product
    {
        return null;
    }
}
