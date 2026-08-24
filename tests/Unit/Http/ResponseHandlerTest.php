<?php

use GustavPHP\Gustav\Http\ResponseHandler;
use GustavPHP\Gustav\Router\RouteCompiler;
use GustavPHP\Tests\Fixtures\{AmbiguousResponseController, NullableResponseController, NullableViewController, UnsupportedResponseController, UntypedResponseController};
use GustavPHP\Tests\Integration\Routes\{Kernel, Responses};

it('requires route handlers to declare one response type', function (string $controller, string $message) {
    expect(fn () => RouteCompiler::compile($controller))
        ->toThrow(LogicException::class, $message);
})->with([
    'missing return type' => [UntypedResponseController::class, 'must declare one response type'],
    'ambiguous return union' => [AmbiguousResponseController::class, 'must declare one response type'],
    'nullable response object' => [NullableResponseController::class, 'must return a non-null response object'],
    'nullable view' => [NullableViewController::class, 'must return a non-null view'],
    'unsupported inferred JSON type' => [UnsupportedResponseController::class, 'unsupported inferred JSON response type mixed'],
]);

it('compiles only deliberate response objects for exception handlers', function (string $class, string $method) {
    expect(ResponseHandler::compileExplicit(
        new ReflectionMethod($class, $method),
        "Exception handler {$class}::__invoke()",
    ))->toBeInstanceOf(ResponseHandler::class);
})->with([
    'Gustav response' => [Kernel::class, 'auth'],
    'PSR response' => [Kernel::class, 'psrResponse'],
    'view' => [Responses::class, 'directView'],
]);

it('rejects inferred JSON values for exception handlers', function () {
    $method = new ReflectionMethod(Responses::class, 'directCollection');

    expect(fn () => ResponseHandler::compileExplicit(
        $method,
        'Exception handler Example\\Invalid::__invoke()',
    ))->toThrow(
        LogicException::class,
        'must return Response, ResponseInterface, or View',
    );
});
