<?php

namespace GustavPHP\Tests\CommandFixtures\Module\Commands;

use GustavPHP\Gustav\Attribute\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[Command('module:ping', description: 'Ping a module')]
final class PingCommand
{
    public function __construct(private readonly SymfonyStyle $output)
    {
    }

    public function __invoke(): void
    {
        $this->output->writeln('pong');
    }
}
