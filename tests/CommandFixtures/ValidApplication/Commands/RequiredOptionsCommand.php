<?php

namespace GustavPHP\Tests\CommandFixtures\ValidApplication\Commands;

use GustavPHP\Gustav\Attribute\{Command, Option};
use Symfony\Component\Console\Command\Command as ExitCode;
use Symfony\Component\Console\Output\OutputInterface;

#[Command('app:required-options')]
final readonly class RequiredOptionsCommand
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function __invoke(
        #[Option]
        int $count,
        #[Option]
        RunMode $mode,
    ): int {
        $this->output->writeln("{$count}:{$mode->value}");

        return ExitCode::SUCCESS;
    }
}
