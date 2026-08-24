<?php

namespace GustavPHP\Gustav\Session;

use GustavPHP\Gustav\Session;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

final readonly class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private Session $session)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (Throwable $exception) {
            $this->session->abort();
            throw $exception;
        }

        return $this->session->complete($response);
    }
}
