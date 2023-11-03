<?php

namespace GustavPHP\Gustav\CLI;

use function realpath;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'dev')]
class DevCommand extends Command
{
    protected static $defaultDescription = 'Starts development server.';

    protected function configure(): void
    {
        $this->setHelp('This command starts the development server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $roadrunner = realpath(getcwd() . DIRECTORY_SEPARATOR . 'rr');
        $command = escapeshellcmd("{$roadrunner} serve -d");

        passthru($command);

        return Command::SUCCESS;
    }
}
