<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\DomainFailure;

#[ExceptionHandler]
final readonly class DomainFailureHandler
{
    public function __invoke(DomainFailure $exception): Response
    {
        return new Response(
            status: 404,
            headers: ['X-Exception-Handler' => 'domain'],
            body: $exception->getMessage(),
        );
    }
}
