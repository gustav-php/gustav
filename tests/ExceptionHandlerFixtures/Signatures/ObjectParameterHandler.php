<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class ObjectParameterHandler
{
    public function __invoke(object $exception): Response
    {
        return new Response(body: get_debug_type($exception));
    }
}
