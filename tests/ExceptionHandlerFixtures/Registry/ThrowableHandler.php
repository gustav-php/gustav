<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use Throwable;

#[ExceptionHandler]
final readonly class ThrowableHandler
{
    public function __invoke(Throwable $exception): Response
    {
        return new Response(
            status: 500,
            headers: ['X-Handler' => 'throwable'],
            body: $exception->getMessage(),
        );
    }
}
