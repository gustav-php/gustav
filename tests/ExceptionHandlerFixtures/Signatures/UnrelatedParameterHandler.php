<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class UnrelatedParameterHandler
{
    public function __invoke(UnrelatedProblem $exception): Response
    {
        return new Response(body: get_debug_type($exception));
    }
}
