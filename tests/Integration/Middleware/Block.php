<?php

namespace GustavPHP\Tests\Integration\Middleware;

use GustavPHP\Gustav\Middleware\Base;
use Nyholm\Psr7\Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class Block extends Base
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new Response(429, [], 'blocked');
    }
}
