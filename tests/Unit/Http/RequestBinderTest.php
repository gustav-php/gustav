<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Tests\Fixtures\{AmbiguousInputController, MultipleInputController};

it('rejects ambiguous input unions during route registration', function () {
    emptyApplication()->addRoutes([AmbiguousInputController::class]);
})->throws(LogicException::class, 'ambiguous unions');

it('requires exactly one source attribute during route registration', function () {
    emptyApplication()->addRoutes([MultipleInputController::class]);
})->throws(LogicException::class, 'exactly one request input attribute');

function emptyApplication(): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\Empty',
        cache: sys_get_temp_dir(),
    ));
}
