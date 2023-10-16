<?php

namespace GustavPHP\Gustav\Internal\Dev;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'serve')]
class ServeCommand extends Command
{
    protected static $defaultDescription = 'Starts development server.';

    protected function configure(): void
    {
        $this
            ->setHelp('This command starts the development server.')
            ->addArgument('entrypoint', InputArgument::OPTIONAL, 'Entrypoint file', './app/index.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entrypoint = escapeshellarg(realpath(getcwd() . DIRECTORY_SEPARATOR . $input->getArgument('entrypoint')));

        passthru(escapeshellarg(PHP_BINARY) . " {$entrypoint}");

        return Command::SUCCESS;
    }
}
