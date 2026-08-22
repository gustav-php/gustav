<?php

namespace GustavPHP\Tests\Integration\Middleware;

use GustavPHP\Gustav\Middleware\Base;
use GustavPHP\Tests\Integration\Services\RequestState;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class Injected extends Base
{
    public function __construct(private readonly RequestState $state)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request->withAttribute('middleware-service', $this->state->id),
        );
    }
}
