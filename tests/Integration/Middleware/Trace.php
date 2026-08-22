<?php

namespace GustavPHP\Tests\Integration\Middleware;

use GustavPHP\Gustav\Middleware\Base;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class Trace extends Base
{
    public function __construct(private readonly string $name)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $trace = $request->getAttribute('middleware-trace', []);
        $trace[] = $this->name;

        return $handler
            ->handle($request->withAttribute('middleware-trace', $trace))
            ->withAddedHeader('X-Middleware', "{$this->name}-out");
    }
}
