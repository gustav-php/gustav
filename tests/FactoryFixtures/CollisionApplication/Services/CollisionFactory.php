<?php

namespace GustavPHP\Tests\FactoryFixtures\CollisionApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\CollisionApplication\Products\CollisionContract;

#[Factory]
final class CollisionFactory
{
    public function __invoke(): CollisionContract
    {
        return new CollisionService();
    }
}
