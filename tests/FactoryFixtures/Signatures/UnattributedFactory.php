<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

final class UnattributedFactory
{
    public function __invoke(): Product
    {
        return new ProductImplementation();
    }
}
