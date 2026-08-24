<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\InvalidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;

#[ExceptionHandler]
final readonly class InvalidHandler
{
    /** @return array{error: string} */
    public function __invoke(string $exception): array
    {
        return ['error' => $exception];
    }
}
