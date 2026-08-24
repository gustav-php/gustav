<?php

namespace GustavPHP\Tests\FactoryFixtures\ProviderApplication\Products;

final class FactoryProduct implements ProviderContract
{
    public function source(): string
    {
        return 'factory';
    }
}
