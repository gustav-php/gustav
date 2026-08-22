<?php

namespace GustavPHP\Tests\Fixtures\Services;

use GustavPHP\Gustav\Service\{Container, Provider};

class TestProvider implements Provider
{
    public function register(Container $services): void
    {
        $services->bind(ProviderContract::class, ProviderImplementation::class);
    }
}
