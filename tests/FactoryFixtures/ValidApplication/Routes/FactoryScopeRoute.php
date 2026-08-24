<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\ScopedProduct;

#[Controller]
final readonly class FactoryScopeRoute
{
    public function __construct(private ScopedProduct $product)
    {
    }

    /** @return array{id:int} */
    #[Get('/factory-scope')]
    public function show(): array
    {
        return ['id' => $this->product->id];
    }
}
