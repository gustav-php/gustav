<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\{Container, Provider};

#[Factory]
final class ProviderFactory implements Provider
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }

    public function register(Container $services): void
    {
    }
}
