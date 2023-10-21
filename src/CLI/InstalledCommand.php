<?php

namespace GustavPHP\Gustav\CLI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'installed', hidden: true)]
class InstalledCommand extends Command
{
    protected static $defaultDescription = 'Starts development server.';

    protected function configure(): void
    {
        $this
            ->setHelp('This command starts the development server.')
            ->addArgument('address', InputArgument::OPTIONAL, 'Address to listen on', '0.0.0.0:4200')
            ->addArgument('entrypoint', InputArgument::OPTIONAL, 'Entrypoint file', './app/index.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<question>GustavPHP is installed!</question>');
        $output->writeln('');
        $output->writeln('To start the development server, run:');
        $output->writeln('');
        $output->writeln('<info>    php gustav serve</info>');
        $output->writeln('');
        $output->writeln('You can now visit the development server at <href=http://localhost:4200>http://localhost:4200</>');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
