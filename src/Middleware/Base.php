<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Controller\Response as GustavResponse;
use GustavPHP\Gustav\Traits\{Logger, Validate};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

abstract class Base implements MiddlewareInterface
{
    use Logger;
    use Validate;

    /**
     * Legacy request-only middleware hook.
     *
     * Existing middleware may continue overriding this method. New middleware can
     * override process() when it needs to inspect or modify the response.
     */
    public function handle(ServerRequestInterface $request): ServerRequestInterface|GustavResponse|ResponseInterface
    {
        return $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->handle($request);

        if ($result instanceof GustavResponse) {
            return $result->build();
        }
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        return $handler->handle($result);
    }
}
