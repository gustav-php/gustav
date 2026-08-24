<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;

#[ExceptionHandler]
final readonly class UnknownReturnHandler
{
    public function __invoke(DomainFailure $exception): UnknownResponse
    {
        throw $exception;
    }
}
