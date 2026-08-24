<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;
use Psr\Log\{LoggerInterface, NullLogger};

#[Factory(Lifetime::Singleton)]
final class LoggerFactory
{
    public function __invoke(): LoggerInterface
    {
        return new NullLogger();
    }
}
