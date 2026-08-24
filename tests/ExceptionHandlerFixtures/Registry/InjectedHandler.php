<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Registry;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Http\RequestId;
use Psr\Http\Message\ServerRequestInterface;

#[ExceptionHandler]
final class InjectedHandler
{
    private readonly int $instance;
    private static int $nextInstance = 0;

    public function __construct(
        private readonly RequestId $requestId,
        private readonly ServerRequestInterface $request,
    ) {
        $this->instance = ++self::$nextInstance;
    }

    public function __invoke(InjectedFailure $exception): Response
    {
        return new Response(
            status: 418,
            headers: [
                'X-Handler-Instance' => (string) $this->instance,
                'X-Request-ID' => (string) $this->requestId,
                'X-Request-Path' => $this->request->getUri()->getPath(),
            ],
            body: $exception->getMessage(),
        );
    }

    public static function instances(): int
    {
        return self::$nextInstance;
    }

    public static function reset(): void
    {
        self::$nextInstance = 0;
    }
}
