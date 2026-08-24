<?php

namespace GustavPHP\Tests\FactoryFixtures\InvalidLifetimeApplication\Products;

final readonly class InvalidSingletonProduct
{
    public function __construct(public string $value)
    {
    }
}
