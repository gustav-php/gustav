<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Tests\Fixtures\{AmbiguousResponseController, InvalidJsonResponseController, InvalidJsonStatusController, NullableResponseController, UntypedResponseController};

it('requires route handlers to declare one response type', function (string $controller, string $message) {
    expect(fn () => responseApplication()->addRoutes([$controller]))
        ->toThrow(LogicException::class, $message);
})->with([
    'missing return type' => [UntypedResponseController::class, 'must declare one response type'],
    'ambiguous return union' => [AmbiguousResponseController::class, 'must declare one response type'],
    'nullable response object' => [NullableResponseController::class, 'must return a non-null response object'],
    'response object marked as direct JSON' => [InvalidJsonResponseController::class, 'cannot use JsonResponse'],
]);

it('rejects invalid direct JSON response statuses', function () {
    responseApplication()->addRoutes([InvalidJsonStatusController::class]);
})->throws(InvalidArgumentException::class, 'between 100 and 599');

function responseApplication(): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\Empty',
        cache: sys_get_temp_dir(),
    ));
}
