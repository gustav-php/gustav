<?php

namespace GustavPHP\Tests\CommandFixtures\Signatures\Commands;

use GustavPHP\Gustav\Attribute\{Command, Option};

#[Command('invalid:reserved-option')]
final class ReservedOptionCommand
{
    public function __invoke(
        #[Option('help')]
        string $help = '',
    ): int {
        return 0;
    }
}
