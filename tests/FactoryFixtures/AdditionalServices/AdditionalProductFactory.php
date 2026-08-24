<?php

namespace GustavPHP\Tests\FactoryFixtures\AdditionalServices;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Tests\FactoryFixtures\AdditionalServices\Products\{AdditionalContract, AdditionalProduct};

#[Factory]
final class AdditionalProductFactory
{
    public function __invoke(): AdditionalContract
    {
        return new AdditionalProduct();
    }
}
