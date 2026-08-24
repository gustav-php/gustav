<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class NullableReturnHandler
{
    public function __invoke(DomainFailure $exception): ?Response
    {
        return $exception->getMessage() === '' ? null : new Response();
    }
}
