<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\{Response, ResponseFormat};
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\ServerFailure;

#[ExceptionHandler]
final readonly class ServerFailureHandler
{
    public function __invoke(ServerFailure $exception): Response
    {
        return new Response(
            status: 503,
            body: [
                'error' => [
                    'status' => 503,
                    'message' => 'Temporarily unavailable',
                    'failure' => $exception::class,
                ],
            ],
            format: ResponseFormat::Json,
        );
    }
}
