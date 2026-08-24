<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
#[ExceptionHandler]
final readonly class DuplicateAttributeHandler
{
    public function __invoke(DomainFailure $exception): Response
    {
        return new Response(body: $exception->getMessage());
    }
}
