<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\PsrFailure;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

#[ExceptionHandler]
final readonly class PsrFailureHandler
{
    public function __invoke(PsrFailure $exception): ResponseInterface
    {
        return new Response(
            202,
            ['X-Exception-Handler' => 'psr'],
            $exception->getMessage(),
        );
    }
}
