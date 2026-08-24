<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;

#[ExceptionHandler]
final readonly class DtoReturnHandler
{
    public function __invoke(DomainFailure $exception): ResponseDto
    {
        return new ResponseDto();
    }
}
