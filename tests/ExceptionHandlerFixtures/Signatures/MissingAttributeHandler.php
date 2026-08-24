<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Controller\Response;

final readonly class MissingAttributeHandler
{
    public function __invoke(DomainFailure $exception): Response
    {
        return new Response(body: $exception->getMessage());
    }
}
