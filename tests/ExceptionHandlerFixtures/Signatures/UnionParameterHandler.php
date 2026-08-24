<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use RuntimeException;

#[ExceptionHandler]
final readonly class UnionParameterHandler
{
    public function __invoke(DomainFailure|RuntimeException $exception): Response
    {
        return new Response(body: $exception->getMessage());
    }
}
