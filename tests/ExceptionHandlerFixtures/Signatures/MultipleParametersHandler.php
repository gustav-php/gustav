<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class MultipleParametersHandler
{
    public function __invoke(DomainFailure $first, DomainFailure $second): Response
    {
        return new Response(body: $first->getMessage() . $second->getMessage());
    }
}
