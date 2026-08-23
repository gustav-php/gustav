<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\CLI\CommandDefinition;
use GustavPHP\Tests\CommandFixtures\Signatures\Commands\{RequiredBooleanCommand, ReservedOptionCommand};
use GustavPHP\Tests\CommandFixtures\ValidApplication\Commands\ScopeCommand;
use GustavPHP\Tests\CommandFixtures\ValidApplication\Services\{CommandLogger, CommandState};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

function commandTestApplication(
    string $namespace = 'GustavPHP\\Tests\\CommandFixtures\\ValidApplication',
    Mode $mode = Mode::Production,
): Application {
    return new Application(new Configuration(
        mode: $mode,
        namespace: $namespace,
    ));
}

it('discovers commands and binds typed arguments, options, defaults, and enums', function () {
    $kernel = commandTestApplication()->console();
    expect($kernel->has('app:greet'))->toBeTrue();
    $tester = new ApplicationTester($kernel);

    $status = $tester->run([
        'command' => 'app:greet',
        'name' => 'Gustav',
        '--times' => '2',
        '--loud' => true,
        '--mode' => 'safe',
        '--no-color' => true,
        '--tag' => ['framework', 'typed'],
    ]);

    expect($status)->toBe(Command::SUCCESS)
        ->and(json_decode(trim($tester->getDisplay()), true))->toMatchArray([
            'message' => 'HELLO GUSTAV HELLO GUSTAV',
            'mode' => 'safe',
            'punctuation' => null,
            'color' => false,
            'tags' => ['framework', 'typed'],
            'namespace' => 'GustavPHP\\Tests\\CommandFixtures\\ValidApplication',
        ]);

    $defaulted = new ApplicationTester(commandTestApplication()->console());
    $defaulted->run(['command' => 'app:greet', 'name' => 'Gustav']);

    expect(json_decode(trim($defaulted->getDisplay()), true))->toMatchArray([
        'message' => 'Hello Gustav',
        'mode' => 'fast',
        'punctuation' => null,
        'color' => true,
        'tags' => [],
    ]);
});

it('reports invalid scalar and enum options as command input errors', function () {
    $tester = new ApplicationTester(commandTestApplication()->console());

    $status = $tester->run([
        'command' => 'app:greet',
        'name' => 'Gustav',
        '--times' => 'many',
        '--mode' => 'reckless',
    ]);

    expect($status)->toBe(Command::INVALID)
        ->and($tester->getDisplay())->toContain('invalid_integer')
        ->toContain('invalid_enum');
});

it('aggregates scalar, enum, and rule violations without invoking the command', function () {
    $tester = new ApplicationTester(commandTestApplication()->console());

    $status = $tester->run([
        'command' => 'app:greet',
        'name' => 'G',
        '--times' => '0',
        '--mode' => 'reckless',
    ]);
    $display = $tester->getDisplay();

    expect($status)->toBe(Command::INVALID)
        ->and($display)->toContain('Invalid command input')
        ->toContain('argument name')
        ->toContain('min_length')
        ->toContain('option --times')
        ->toContain('min_value')
        ->toContain('option --mode')
        ->toContain('invalid_enum');
});

it('aggregates missing required options', function () {
    $tester = new ApplicationTester(commandTestApplication()->console());

    $status = $tester->run(['command' => 'app:required-options']);

    expect($status)->toBe(Command::INVALID)
        ->and($tester->getDisplay())->toContain('option --count')
        ->toContain('option --mode')
        ->toContain('required');
});

it('uses the invalid-input exit code for missing positional arguments', function () {
    $tester = new ApplicationTester(commandTestApplication()->console());

    $status = $tester->run(['command' => 'app:greet']);

    expect($status)->toBe(Command::INVALID)
        ->and($tester->getDisplay())->toContain('Not enough arguments')
        ->toContain('name');
});

it('isolates and releases command scopes after success and failure', function () {
    CommandLogger::reset();
    $kernel = commandTestApplication()->console();
    $first = new ApplicationTester($kernel);
    $second = new ApplicationTester($kernel);

    expect($first->run(['command' => 'app:scope']))->toBe(Command::SUCCESS);
    $firstId = trim($first->getDisplay());
    $released = ScopeCommand::$capturedScope;
    expect(fn () => $released?->get(CommandState::class))
        ->toThrow(LogicException::class, 'released');

    expect($second->run(['command' => 'app:scope']))->toBe(Command::SUCCESS)
        ->and(trim($second->getDisplay()))->not->toBe($firstId);

    $failed = new ApplicationTester($kernel);
    expect($failed->run(['command' => 'app:scope', '--fail' => true]))
        ->toBe(Command::FAILURE)
        ->and($failed->getDisplay())->toContain('Command failed')
        ->not->toContain('secret command failure')
        ->and(CommandLogger::$records)->toHaveCount(1)
        ->and(CommandLogger::$records[0]['message'])->toBe('Command failed');

    $releasedAfterFailure = ScopeCommand::$capturedScope;
    expect(fn () => $releasedAfterFailure?->get(CommandState::class))
        ->toThrow(LogicException::class, 'released');

    $recovered = new ApplicationTester($kernel);
    expect($recovered->run(['command' => 'app:scope']))->toBe(Command::SUCCESS);
});

it('shows unexpected command messages during development', function () {
    $tester = new ApplicationTester(commandTestApplication(mode: Mode::Development)->console());

    expect($tester->run(['command' => 'app:scope', '--fail' => true]))
        ->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('secret command failure');
});

it('validates command signatures while the application boots', function () {
    commandTestApplication('GustavPHP\\Tests\\CommandFixtures\\InvalidApplication');
})->throws(LogicException::class, 'must declare exactly one command input attribute');

it('rejects duplicate discovered command names', function () {
    commandTestApplication('GustavPHP\\Tests\\CommandFixtures\\DuplicateApplication');
})->throws(LogicException::class, 'app:duplicate');

it('discovers additional command namespaces and treats void as success', function () {
    $application = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\CommandFixtures\\ValidApplication',
        commandNamespaces: ['GustavPHP\\Tests\\CommandFixtures\\Module\\Commands'],
    ));
    $tester = new ApplicationTester($application->console());

    expect($tester->run(['command' => 'module:ping']))->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('pong');
});

it('rejects boolean options without defaults and reserved console options', function (string $class, string $message) {
    try {
        CommandDefinition::compile($class);
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    throw new RuntimeException('Expected invalid command signature to fail');
})->with([
    'required boolean flag' => [RequiredBooleanCommand::class, 'PHP default'],
    'reserved option name' => [ReservedOptionCommand::class, 'reserved option --help'],
]);
