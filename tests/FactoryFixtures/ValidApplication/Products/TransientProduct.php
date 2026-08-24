<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Products;

final readonly class TransientProduct
{
    public function __construct(public int $id)
    {
    }
}
