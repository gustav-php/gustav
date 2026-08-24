<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\Exceptions\DuplicateFailure;

#[ExceptionHandler]
final readonly class SecondHandler
{
    public function __invoke(DuplicateFailure $exception): Response
    {
        return new Response(status: 409, body: $exception->getMessage());
    }
}
