<?php

namespace GustavPHP\Tests\CommandFixtures\InvalidApplication\Commands;

use GustavPHP\Gustav\Attribute\Command;

#[Command('app:invalid')]
final class InvalidCommand
{
    public function __invoke(string $missingInputAttribute): int
    {
        return 0;
    }
}
