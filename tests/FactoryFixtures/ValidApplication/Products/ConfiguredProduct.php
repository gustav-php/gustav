<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Products;

final readonly class ConfiguredProduct implements FactoryContract
{
    public function __construct(public string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
