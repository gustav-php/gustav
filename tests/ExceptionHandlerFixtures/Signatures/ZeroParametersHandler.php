<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class ZeroParametersHandler
{
    public function __invoke(): Response
    {
        return new Response();
    }
}
