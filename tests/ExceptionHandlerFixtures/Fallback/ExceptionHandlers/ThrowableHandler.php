<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Fallback\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use Throwable;

#[ExceptionHandler]
final readonly class ThrowableHandler
{
    public function __invoke(Throwable $exception): Response
    {
        return new Response(
            status: 499,
            headers: ['X-Exception-Handler' => 'fallback'],
            body: 'fallback: ' . $exception::class,
        );
    }
}
