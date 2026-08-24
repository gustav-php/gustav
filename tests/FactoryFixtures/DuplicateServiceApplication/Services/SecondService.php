<?php

namespace GustavPHP\Tests\FactoryFixtures\DuplicateServiceApplication\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Tests\FactoryFixtures\DuplicateServiceApplication\Products\DuplicateContract;

#[Service(as: DuplicateContract::class)]
final class SecondService implements DuplicateContract
{
    public function source(): string
    {
        return 'second';
    }
}
