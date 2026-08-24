<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class SpecificHandler
{
    public function __invoke(SpecificFailure $exception): Response
    {
        return new Response(
            status: 409,
            headers: ['X-Handler' => 'specific'],
            body: $exception->getMessage(),
        );
    }
}
