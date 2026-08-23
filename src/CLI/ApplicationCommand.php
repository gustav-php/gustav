<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Logger\ExceptionReporter;
use GustavPHP\Gustav\Mode;
use GustavPHP\Gustav\Service\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\{ConsoleOutputInterface, OutputInterface};
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/** @internal */
final class ApplicationCommand extends Command
{
    private CommandBinder $binder;

    public function __construct(
        private readonly CommandDefinition $definition,
        private readonly Container $services,
        private readonly Mode $mode,
    ) {
        $this->binder = new CommandBinder($definition);

        parent::__construct($definition->name);
    }

    protected function configure(): void
    {
        $this->setDescription($this->definition->description);
        $this->setHidden($this->definition->hidden);

        foreach ($this->definition->parameters as $parameter) {
            if ($parameter->kind === InputKind::Argument) {
                $mode = $parameter->hasDefault ? InputArgument::OPTIONAL : InputArgument::REQUIRED;
                if ($parameter->converter->isArray()) {
                    $mode |= InputArgument::IS_ARRAY;
                }
                $this->addArgument(
                    $parameter->inputName,
                    $mode,
                    $parameter->description,
                );

                continue;
            }

            $mode = InputOption::VALUE_REQUIRED;
            $default = null;
            if ($parameter->converter->isBoolean()) {
                if ($parameter->hasDefault && $parameter->defaultValue !== false) {
                    $mode = InputOption::VALUE_NEGATABLE;
                    $default = $parameter->defaultValue;
                } else {
                    $mode = InputOption::VALUE_NONE;
                }
            } elseif ($parameter->converter->isArray()) {
                $mode |= InputOption::VALUE_IS_ARRAY;
            }

            $this->addOption(
                $parameter->inputName,
                $parameter->shortcut,
                $mode,
                $parameter->description,
                $default,
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scope = null;

        try {
            $scope = $this->services->createScope([
                InputInterface::class => $input,
                OutputInterface::class => $output,
                SymfonyStyle::class => new SymfonyStyle($input, $output),
            ]);
            $arguments = $this->binder->bind($input);
            $handler = $scope->make($this->definition->class);
            $result = $this->definition->method->invokeArgs($handler, $arguments);

            return $result ?? self::SUCCESS;
        } catch (CommandInputException $exception) {
            $this->renderInputFailure($exception, $output);

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->reportFailure($exception, $scope);
            $message = $this->mode === Mode::Production
                ? 'Command failed'
                : ($exception->getMessage() ?: $exception::class);
            $this->errorOutput($output)->writeln(
                '<error>' . OutputFormatter::escape($message) . '</error>',
            );

            return self::FAILURE;
        } finally {
            $scope?->release();
        }
    }

    private function errorOutput(OutputInterface $output): OutputInterface
    {
        return $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;
    }

    private function renderInputFailure(CommandInputException $exception, OutputInterface $output): void
    {
        $output = $this->errorOutput($output);
        $output->writeln('<error>Invalid command input</error>');

        foreach ($exception->violations as $violation) {
            $location = "{$violation->source} {$violation->path}";
            $output->writeln(sprintf(
                '  <comment>%s</comment> [%s] %s',
                OutputFormatter::escape($location),
                OutputFormatter::escape($violation->code),
                OutputFormatter::escape($violation->message),
            ));
        }
    }

    private function reportFailure(Throwable $exception, ?Container $scope): void
    {
        if ($scope === null) {
            return;
        }

        try {
            $reporter = $scope->get(ExceptionReporter::class);
            if ($reporter instanceof ExceptionReporter) {
                $reporter->reportCommand($exception, $this->definition->name);
            }
        } catch (Throwable) {
            // Reporting failures must not change the command result.
        }
    }
}
