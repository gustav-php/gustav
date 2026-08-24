<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Middlewares;

use GustavPHP\Gustav\Attribute\GlobalMiddleware;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\OuterFailure;
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

        return $response->withHeader('X-Inspected-Status', (string) $response->getStatusCode());
    }
}
