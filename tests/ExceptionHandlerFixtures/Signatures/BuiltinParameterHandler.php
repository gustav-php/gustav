<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class BuiltinParameterHandler
{
    public function __invoke(string $exception): Response
    {
        return new Response(body: $exception);
    }
}
