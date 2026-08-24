<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\{Response, ResponseFormat};
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\ClientFailure;

#[ExceptionHandler]
final readonly class ClientFailureHandler
{
    public function __invoke(ClientFailure $exception): Response
    {
        return new Response(
            status: 422,
            body: [
                'error' => [
                    'status' => 422,
                    'message' => $exception->getMessage(),
                ],
            ],
            format: ResponseFormat::Json,
        );
    }
}
