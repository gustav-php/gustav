<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class UnknownParameterHandler
{
    public function __invoke(UnknownFailure $exception): Response
    {
        return new Response(body: get_debug_type($exception));
    }
}
