<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class MissingParameterTypeHandler
{
    public function __invoke($exception): Response
    {
        return new Response(body: $exception);
    }
}
