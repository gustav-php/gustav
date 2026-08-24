<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

#[ExceptionHandler]
final readonly class PsrHandler
{
    public function __invoke(PsrFailure $exception): ResponseInterface
    {
        return new Response(202, ['X-Handler' => 'psr'], $exception->getMessage());
    }
}
