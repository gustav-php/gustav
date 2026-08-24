<?php

namespace GustavPHP\Tests\FactoryFixtures\DuplicateServiceApplication\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Tests\FactoryFixtures\DuplicateServiceApplication\Products\DuplicateContract;

#[Service(as: DuplicateContract::class)]
final class FirstService implements DuplicateContract
{
    public function source(): string
    {
        return 'first';
    }
}
