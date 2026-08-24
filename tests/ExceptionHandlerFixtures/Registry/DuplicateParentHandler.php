<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class DuplicateParentHandler
{
    public function __invoke(ParentFailure $exception): Response
    {
        return new Response(status: 400, body: $exception->getMessage());
    }
}
