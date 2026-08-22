<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Http\CallableRequestHandler;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final readonly class Pipeline implements RequestHandlerInterface
{
    /**
     * @param array<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private array $middlewares,
        private RequestHandlerInterface $fallback,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request, 0);
    }

    private function dispatch(ServerRequestInterface $request, int $index): ResponseInterface
    {
        $middleware = $this->middlewares[$index] ?? null;
        if ($middleware === null) {
            return $this->fallback->handle($request);
        }

        return $middleware->process(
            $request,
            new CallableRequestHandler(
                fn (ServerRequestInterface $nextRequest): ResponseInterface => $this->dispatch($nextRequest, $index + 1),
            ),
        );
    }
}
