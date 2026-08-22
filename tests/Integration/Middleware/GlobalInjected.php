<?php

namespace GustavPHP\Tests\Integration\Middleware;

use GustavPHP\Gustav\Middleware\Base;
use GustavPHP\Tests\Integration\Services\SingletonState;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class GlobalInjected extends Base
{
    public function __construct(private readonly SingletonState $state)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler
            ->handle($request)
            ->withHeader('X-Singleton-Service', (string) $this->state->id);
    }
}
