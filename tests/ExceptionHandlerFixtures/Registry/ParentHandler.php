<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class ParentHandler
{
    public function __invoke(ParentFailure $exception): Response
    {
        return new Response(
            status: 404,
            headers: ['X-Handler' => 'parent'],
            body: $exception->getMessage(),
        );
    }
}
