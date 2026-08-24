<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\SpecificFailure;

#[ExceptionHandler]
final readonly class SpecificFailureHandler
{
    public function __invoke(SpecificFailure $exception): Response
    {
        return new Response(
            status: 409,
            headers: ['X-Exception-Handler' => 'specific'],
            body: $exception->getMessage(),
        );
    }
}
