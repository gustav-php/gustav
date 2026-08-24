<?php

namespace GustavPHP\Tests\FactoryFixtures\InvalidLifetimeApplication\Services;

use GustavPHP\Gustav\Attribute\Service;

#[Service]
final class ScopedDependency
{
    public function value(): string
    {
        return 'scoped';
    }
}
