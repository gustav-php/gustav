<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Products;

final readonly class ScopedProduct
{
    public function __construct(public int $id)
    {
    }
}
