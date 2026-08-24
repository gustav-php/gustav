<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\View;

#[ExceptionHandler]
final readonly class ViewHandler
{
    public function __invoke(ViewFailure $exception): View
    {
        return new View(
            'failure',
            ['message' => $exception->getMessage()],
            status: 410,
            headers: ['X-Handler' => 'view'],
        );
    }
}
