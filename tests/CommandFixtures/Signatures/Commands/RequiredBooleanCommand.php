<?php

namespace GustavPHP\Tests\CommandFixtures\Signatures\Commands;

use GustavPHP\Gustav\Attribute\{Command, Option};

#[Command('invalid:required-boolean')]
final class RequiredBooleanCommand
{
    public function __invoke(
        #[Option]
        bool $force,
    ): int {
        return 0;
    }
}
