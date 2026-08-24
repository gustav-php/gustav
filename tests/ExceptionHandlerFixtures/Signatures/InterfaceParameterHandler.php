<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class InterfaceParameterHandler
{
    public function __invoke(ThrowableContract $exception): Response
    {
        return new Response(body: $exception->getMessage());
    }
}
