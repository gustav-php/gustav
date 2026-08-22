<?php

namespace GustavPHP\Gustav\Auth;

use GustavPHP\Gustav\Middleware\Base;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware extends Base
{
    public function __construct(private readonly Authenticator $authenticator)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $this->authenticator->authenticate($request);

        return $handler->handle(
            $request
                ->withAttribute(Identity::class, $identity)
                ->withAttribute('identity', $identity),
        );
    }
}
