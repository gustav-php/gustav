<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Middlewares;

use GustavPHP\Gustav\Attribute\GlobalMiddleware;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\{HandlerFailure, OuterFailure};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

#[GlobalMiddleware]
final readonly class InspectMappedResponse implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() === '/handlers/outer') {
            throw new OuterFailure('outer middleware mapped');
        }

        $response = $handler->handle($request);

        if ($request->getUri()->getPath() === '/handlers/chained-handler-failure') {
            throw new HandlerFailure('outer domain secret');
        }

        return $response->withHeader('X-Inspected-Status', (string) $response->getStatusCode());
    }
}
