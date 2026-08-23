<?php

use GustavPHP\Gustav\Attribute\{Argument, Command, Option};

it('describes commands, arguments, and options', function () {
    $command = new Command('users:sync', 'Synchronize users', hidden: true);
    $argument = new Argument('tenant', 'Tenant identifier');
    $option = new Option('dry-run', 'd', 'Do not persist changes');

    expect($command->name)->toBe('users:sync')
        ->and($command->description)->toBe('Synchronize users')
        ->and($command->hidden)->toBeTrue()
        ->and($argument->name)->toBe('tenant')
        ->and($option->name)->toBe('dry-run')
        ->and($option->shortcut)->toBe('d');
});

it('rejects invalid command metadata', function (Closure $create) {
    $create();
})->with([
    'command name' => fn () => new Command('Invalid Name'),
    'argument name' => fn () => new Argument('--tenant'),
    'option name' => fn () => new Option('dry_run'),
    'option shortcut' => fn () => new Option(shortcut: 'dry'),
])->throws(InvalidArgumentException::class);
