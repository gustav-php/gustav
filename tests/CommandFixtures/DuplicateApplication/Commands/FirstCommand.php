<?php

namespace GustavPHP\Tests\CommandFixtures\DuplicateApplication\Commands;

use GustavPHP\Gustav\Attribute\Command;

#[Command('app:duplicate')]
final class FirstCommand
{
    public function __invoke(): int
    {
        return 0;
    }
}
