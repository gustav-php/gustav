<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;

#[ExceptionHandler]
final readonly class BuiltinReturnHandler
{
    /** @return array{message: string} */
    public function __invoke(DomainFailure $exception): array
    {
        return ['message' => $exception->getMessage()];
    }
}
