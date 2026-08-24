<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\{Factory, Service};

#[Factory]
#[Service]
final class ServiceFactory
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
