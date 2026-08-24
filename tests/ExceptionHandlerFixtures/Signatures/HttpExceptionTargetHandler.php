<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Http\Exception\HttpException;

#[ExceptionHandler]
final readonly class HttpExceptionTargetHandler
{
    public function __invoke(HttpException $exception): Response
    {
        return new Response(status: $exception->getStatusCode());
    }
}
