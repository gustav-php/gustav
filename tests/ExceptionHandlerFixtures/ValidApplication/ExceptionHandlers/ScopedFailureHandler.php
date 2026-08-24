<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\{Response, ResponseFormat};
use GustavPHP\Gustav\Http\RequestId;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\ScopedFailure;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Services\ScopeProbe;
use Psr\Http\Message\ServerRequestInterface;

#[ExceptionHandler]
final class ScopedFailureHandler
{
    private readonly int $instance;
    private static int $next = 0;

    public function __construct(
        private readonly RequestId $requestId,
        private readonly ServerRequestInterface $request,
        private readonly ScopeProbe $scope,
    ) {
        $this->instance = ++self::$next;
    }

    public function __invoke(ScopedFailure $exception): Response
    {
        return new Response(
            status: 418,
            body: [
                'message' => $exception->getMessage(),
                'handler' => $this->instance,
                'scope' => $this->scope->id,
                'requestId' => (string) $this->requestId,
                'path' => $this->request->getUri()->getPath(),
            ],
            format: ResponseFormat::Json,
        );
    }

    public static function instances(): int
    {
        return self::$next;
    }

    public static function reset(): void
    {
        self::$next = 0;
    }
}
