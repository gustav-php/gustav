<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Commands;

use GustavPHP\Gustav\Attribute\Command;
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\ScopedProduct;

#[Command('factory:scope')]
final class FactoryScopeCommand
{
    /** @var list<int> */
    public static array $productIds = [];

    public function __construct(private ScopedProduct $product)
    {
    }

    public function __invoke(): void
    {
        self::$productIds[] = $this->product->id;
    }
}
