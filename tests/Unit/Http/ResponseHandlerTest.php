<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Tests\Fixtures\{AmbiguousResponseController, NullableResponseController, UnsupportedResponseController, UntypedResponseController};

it('requires route handlers to declare one response type', function (string $controller, string $message) {
    expect(fn () => responseApplication()->addRoutes([$controller]))
        ->toThrow(LogicException::class, $message);
})->with([
    'missing return type' => [UntypedResponseController::class, 'must declare one response type'],
    'ambiguous return union' => [AmbiguousResponseController::class, 'must declare one response type'],
    'nullable response object' => [NullableResponseController::class, 'must return a non-null response object'],
    'unsupported inferred JSON type' => [UnsupportedResponseController::class, 'unsupported inferred JSON response type mixed'],
]);

function responseApplication(): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\Empty',
        cache: sys_get_temp_dir(),
    ));
}
