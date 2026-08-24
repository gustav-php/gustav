<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class MixedParameterHandler
{
    public function __invoke(mixed $exception): Response
    {
        return new Response(body: get_debug_type($exception));
    }
}
