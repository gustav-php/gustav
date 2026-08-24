<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Tests\FactoryFixtures\ValidApplication\Products\FailingProduct;

#[Controller]
final readonly class FailingFactoryRoute
{
    public function __construct(private FailingProduct $product)
    {
    }

    /** @return array{resolved:bool} */
    #[Get('/factory-failure')]
    public function show(): array
    {
        return ['resolved' => $this->product instanceof FailingProduct];
    }
}
