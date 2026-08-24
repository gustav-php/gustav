<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Products;

final readonly class SingletonProduct
{
    public function __construct(public int $id)
    {
    }
}
