<?php

namespace GustavPHP\Tests\CommandFixtures\ValidApplication\Commands;

use GustavPHP\Gustav\Attribute\Command;
use GustavPHP\Gustav\Service\Container;
use GustavPHP\Tests\CommandFixtures\ValidApplication\Services\CommandState;
use RuntimeException;
use Symfony\Component\Console\Command\Command as ExitCode;
use Symfony\Component\Console\Output\OutputInterface;

#[Command('app:scope')]
final class ScopeCommand
{
    public static ?Container $capturedScope = null;

    public function __construct(
        private readonly Container $scope,
        private readonly CommandState $state,
        private readonly OutputInterface $output,
    ) {
    }

    public function __invoke(
        #[\GustavPHP\Gustav\Attribute\Option]
        bool $fail = false,
    ): int {
        self::$capturedScope = $this->scope;
        if ($fail) {
            throw new RuntimeException('secret command failure');
        }

        $this->output->writeln((string) $this->state->id);

        return ExitCode::SUCCESS;
    }
}
