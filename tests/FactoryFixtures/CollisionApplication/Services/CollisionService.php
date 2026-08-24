<?php

namespace GustavPHP\Tests\FactoryFixtures\CollisionApplication\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Tests\FactoryFixtures\CollisionApplication\Products\CollisionContract;

#[Service(as: CollisionContract::class)]
final class CollisionService implements CollisionContract
{
    public function source(): string
    {
        return 'service';
    }
}
