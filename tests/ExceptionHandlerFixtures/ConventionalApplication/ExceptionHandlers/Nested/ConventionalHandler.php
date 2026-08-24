<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ConventionalApplication\ExceptionHandlers\Nested;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\ConventionalApplication\Exceptions\ConventionalFailure;

#[ExceptionHandler]
final readonly class ConventionalHandler
{
    public function __invoke(ConventionalFailure $exception): Response
    {
        return new Response(status: 404, body: $exception->getMessage());
    }
}
