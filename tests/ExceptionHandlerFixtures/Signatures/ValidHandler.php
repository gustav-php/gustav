<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class ValidHandler
{
    public function __invoke(DomainFailure $exception): Response
    {
        return new Response(status: 409, body: $exception->getMessage());
    }
}
