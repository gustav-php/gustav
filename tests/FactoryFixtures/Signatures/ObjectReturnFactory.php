<?php

namespace GustavPHP\Tests\FactoryFixtures\Signatures;

use GustavPHP\Gustav\Attribute\Factory;
use stdClass;

#[Factory]
final class ObjectReturnFactory
{
    public function __invoke(): object
    {
        return new stdClass();
    }
}
