<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Additional\Handlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\Additional\AdditionalFailure;

#[ExceptionHandler]
final readonly class AdditionalHandler
{
    public function __invoke(AdditionalFailure $exception): Response
    {
        return new Response(status: 409, body: $exception->getMessage());
    }
}
