<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\OuterFailure;

#[ExceptionHandler]
final readonly class OuterFailureHandler
{
    public function __invoke(OuterFailure $exception): Response
    {
        return new Response(
            status: 429,
            headers: ['Retry-After' => '5'],
            body: $exception->getMessage(),
        );
    }
}
