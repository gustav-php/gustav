<?php

namespace GustavPHP\Tests\CommandFixtures\ValidApplication\Commands;

use GustavPHP\Gustav\Attribute\{Argument, Command, Option, Validate};
use GustavPHP\Gustav\Configuration;
use GustavPHP\Gustav\Validation\Common\{Integer, Text};
use GustavPHP\Tests\CommandFixtures\ValidApplication\Services\CommandState;
use Symfony\Component\Console\Command\Command as ExitCode;
use Symfony\Component\Console\Output\OutputInterface;

#[Command('app:greet', description: 'Render a typed greeting')]
final readonly class GreetCommand
{
    public function __construct(
        private OutputInterface $output,
        private CommandState $state,
        private Configuration $configuration,
    ) {
    }

    public function __invoke(
        #[Argument(description: 'Name to greet')]
        #[Validate(new Text(minLength: 2))]
        string $name,
        #[Option(shortcut: 't', description: 'Number of greetings')]
        #[Validate(new Integer(min: 1, max: 3))]
        int $times = 1,
        #[Option('loud', description: 'Use uppercase output')]
        bool $loud = false,
        #[Option(description: 'Execution mode')]
        RunMode $mode = RunMode::Fast,
        #[Option(description: 'Greeting punctuation')]
        ?string $punctuation = null,
        #[Option(description: 'Use colored output')]
        bool $color = true,
        #[Option(description: 'Greeting tags')]
        array $tag = [],
    ): int {
        $message = implode(' ', array_fill(0, $times, "Hello {$name}"));
        if ($loud) {
            $message = strtoupper($message);
        }

        $this->output->writeln((string) json_encode([
            'message' => $message,
            'mode' => $mode->value,
            'punctuation' => $punctuation,
            'color' => $color,
            'tags' => $tag,
            'scope' => $this->state->id,
            'namespace' => $this->configuration->namespace,
        ], JSON_THROW_ON_ERROR));

        return ExitCode::SUCCESS;
    }
}
