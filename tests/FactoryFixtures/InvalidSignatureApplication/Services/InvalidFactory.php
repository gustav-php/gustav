<?php

namespace GustavPHP\Tests\FactoryFixtures\InvalidSignatureApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;

#[Factory]
final class InvalidFactory
{
    public function __invoke(): string
    {
        return 'invalid';
    }
}
