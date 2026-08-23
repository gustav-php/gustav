<?php

namespace GustavPHP\Gustav\CLI;

use Composer\InstalledVersions;
use GustavPHP\Gustav\Mode;
use GustavPHP\Gustav\Service\Container;
use LogicException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Kernel extends Application
{
    /**
     * @param list<CommandDefinition> $commands
     */
    public function __construct(
        ?Container $services = null,
        array $commands = [],
        Mode $mode = Mode::Development,
    ) {
        parent::__construct(
            'GustavPHP',
            InstalledVersions::getPrettyVersion('gustav-php/gustav') ?? 'development',
        );
        $this->setAutoExit(false);
        $this->addCommand(new DevCommand());
        $this->addCommand(new InstalledCommand());
        $this->addCommand(new StartCommand());

        if ($services === null && $commands !== []) {
            throw new LogicException('Application commands require an application service container');
        }

        foreach ($commands as $definition) {
            if (array_key_exists($definition->name, $this->all())) {
                throw new LogicException("Command name '{$definition->name}' is already registered");
            }
            $this->addCommand(new ApplicationCommand($definition, $services, $mode));
        }
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::doRun($input, $output);
        } catch (ConsoleRuntimeException $exception) {
            throw new \RuntimeException($exception->getMessage(), Command::INVALID);
        }
    }
}
