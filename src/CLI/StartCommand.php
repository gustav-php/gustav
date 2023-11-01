<?php

namespace GustavPHP\Gustav\CLI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'start')]
class StartCommand extends Command
{
    protected static $defaultDescription = 'Starts production server.';

    protected function configure(): void
    {
        $this->setHelp('This command starts the production server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $roadrunner = realpath(getcwd() . DIRECTORY_SEPARATOR . 'rr');
        $command = escapeshellcmd("{$roadrunner} serve -c ./.rr.prod.yaml");

        passthru($command);

        return Command::SUCCESS;
    }
}
