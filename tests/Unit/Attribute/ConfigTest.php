<?php

use GustavPHP\Gustav\Attribute\Env;

it('maps a constructor parameter to an environment variable', function () {
    expect((new Env('APP_NAME'))->name)->toBe('APP_NAME');
});

it('rejects invalid environment variable names', function (string $name) {
    new Env($name);
})->with(['', 'APP=NAME', "APP\0NAME"])->throws(InvalidArgumentException::class);
