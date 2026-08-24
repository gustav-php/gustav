<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class VoidReturnFactory
{
    public function __invoke(): void
    {
    }
}
