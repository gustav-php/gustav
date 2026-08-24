<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;
use stdClass;

#[Factory]
final class MixedReturnFactory
{
    public function __invoke(): mixed
    {
        return new stdClass();
    }
}
