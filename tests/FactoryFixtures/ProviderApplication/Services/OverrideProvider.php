<?php

namespace GustavPHP\Tests\FactoryFixtures\ProviderApplication\Services;

use GustavPHP\Gustav\Service\{Container, Provider};
use GustavPHP\Tests\FactoryFixtures\ProviderApplication\Products\{ProviderContract, ProviderProduct};

final class OverrideProvider implements Provider
{
    public function register(Container $services): void
    {
        $services->scoped(ProviderContract::class, ProviderProduct::class);
    }
}
