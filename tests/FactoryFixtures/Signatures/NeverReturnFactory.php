<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;
use RuntimeException;

#[Factory]
final class NeverReturnFactory
{
    public function __invoke(): never
    {
        throw new RuntimeException('must not be invoked');
    }
}
