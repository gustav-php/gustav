<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class ArrayReturnFactory
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [];
    }
}
