<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\View;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\ViewFailure;

#[ExceptionHandler]
final readonly class ViewFailureHandler
{
    public function __invoke(ViewFailure $exception): View
    {
        return new View(
            'exception-handler',
            ['message' => $exception->getMessage()],
            status: 410,
            headers: ['X-Exception-Handler' => 'view'],
        );
    }
}
