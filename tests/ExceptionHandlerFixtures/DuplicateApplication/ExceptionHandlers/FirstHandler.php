<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\Exceptions\DuplicateFailure;

#[ExceptionHandler]
final readonly class FirstHandler
{
    public function __invoke(DuplicateFailure $exception): Response
    {
        return new Response(status: 400, body: $exception->getMessage());
    }
}
