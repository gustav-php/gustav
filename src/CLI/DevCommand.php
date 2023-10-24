<?php

namespace GustavPHP\Gustav\CLI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'dev')]
class DevCommand extends Command
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
        $entrypoint = realpath(getcwd() . DIRECTORY_SEPARATOR . $input->getArgument('entrypoint'));
        if (!$entrypoint) {
            $output->writeln('<error>Entrypoint file not found.</error>');
            return Command::FAILURE;
        }
        $entrypoint = escapeshellarg($entrypoint);
        $command = escapeshellarg(PHP_BINARY) . " {$entrypoint}";
        $latest = $this->getLatestModificationTimestamp();
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $counter = 0;
        while (true) {
            $output->write($process->getIncrementalOutput());
            if (++$counter >= 100 && $this->getLatestModificationTimestamp() > $latest) {
                $latest = $this->getLatestModificationTimestamp();
                $output->writeln('<info>Changes detected, restarting server...</info>');
                $process->stop();
                $process->start();
            }

            \usleep(10000);
            if ($process->isTerminated()) {
                break;
            }
            if ($counter >= 100) {
                $counter = 0;
            }
        }

        return Command::SUCCESS;
    }

    protected function getLatestModificationTimestamp(): int
    {
        $root = getcwd() . DIRECTORY_SEPARATOR;
        $latest = 0;
        foreach (['src', 'app', 'views'] as $directory) {
            $path = \realpath($root . DIRECTORY_SEPARATOR . $directory);
            if (!$path) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                /**
                 * @var \SplFileInfo $file
                 */
                if ($file->getMTime() > $latest) {
                    $latest = $file->getMTime();
                }
            }
        }

        return $latest;
    }
}
