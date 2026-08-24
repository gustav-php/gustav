<?php

namespace GustavPHP\Tests\FactoryFixtures\ProviderApplication\Products;

final class ProviderProduct implements ProviderContract
{
    public function source(): string
    {
        return 'provider';
    }
}
