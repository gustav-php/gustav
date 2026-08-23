<?php

use GustavPHP\Gustav\Router\RouteCompiler;
use GustavPHP\Tests\Fixtures\{AmbiguousResponseController, NullableResponseController, UnsupportedResponseController, UntypedResponseController};

it('requires route handlers to declare one response type', function (string $controller, string $message) {
    expect(fn () => RouteCompiler::compile($controller))
        ->toThrow(LogicException::class, $message);
})->with([
    'missing return type' => [UntypedResponseController::class, 'must declare one response type'],
    'ambiguous return union' => [AmbiguousResponseController::class, 'must declare one response type'],
    'nullable response object' => [NullableResponseController::class, 'must return a non-null response object'],
    'unsupported inferred JSON type' => [UnsupportedResponseController::class, 'unsupported inferred JSON response type mixed'],
]);
