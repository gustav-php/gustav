<?php

namespace GustavPHP\Tests\Fixtures\Middlewares;

use GustavPHP\Gustav\Attribute\GlobalMiddleware;
use GustavPHP\Gustav\Middleware\Base;
use GustavPHP\Tests\Fixtures\Services\ProviderContract;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

#[GlobalMiddleware(priority: -100)]
class DiscoveredMiddleware extends Base
{
    public function __construct(private readonly ProviderContract $service)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler
            ->handle($request)
            ->withHeader('X-Discovered-Middleware', $this->service->value());
    }
}
