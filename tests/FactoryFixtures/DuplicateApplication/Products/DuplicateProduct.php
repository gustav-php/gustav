<?php

namespace GustavPHP\Tests\FactoryFixtures\DuplicateApplication\Products;

final readonly class DuplicateProduct
{
    public function __construct(public string $source)
    {
    }
}
