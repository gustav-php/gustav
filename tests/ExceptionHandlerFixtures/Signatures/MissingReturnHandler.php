<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;

#[ExceptionHandler]
final readonly class MissingReturnHandler
{
    public function __invoke(DomainFailure $exception)
    {
        return $exception->getMessage();
    }
}
