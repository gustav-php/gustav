<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{AuthUser, Middleware, Request, Route};
use GustavPHP\Gustav\Auth\{AuthenticationMiddleware, Identity};
use GustavPHP\Gustav\Auth\Exception\ForbiddenException;
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Tests\Integration\Auth\HeaderAuthenticator;
use GustavPHP\Tests\Integration\Middleware\{Block, Legacy, Trace};
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use RuntimeException;

#[Middleware(new Trace('controller'))]
class Kernel extends Controller\Base
{
    #[Route('/kernel/auth')]
    #[Middleware(new AuthenticationMiddleware(new HeaderAuthenticator()))]
    public function auth(#[AuthUser] Identity $identity): Controller\Response
    {
        return $this->json([
            'id' => $identity->getIdentifier(),
            'roles' => $identity->getRoles(),
        ]);
    }

    #[Route('/kernel/auth/missing')]
    public function authMissing(#[AuthUser] Identity $identity): Controller\Response
    {
        return $this->plaintext($identity->getIdentifier());
    }

    #[Route('/kernel/blocked')]
    #[Middleware(new Block())]
    public function blocked(): Controller\Response
    {
        return $this->plaintext('controller should not run');
    }

    #[Route('/kernel/error')]
    public function error(): Controller\Response
    {
        throw new HttpException(418, 'Short and stout', ['X-Error' => 'mapped']);
    }

    #[Route('/kernel/forbidden')]
    public function forbidden(): Controller\Response
    {
        throw new ForbiddenException('Insufficient permissions');
    }

    #[Route('/kernel/legacy')]
    #[Middleware(new Legacy())]
    public function legacy(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->plaintext(implode(',', $request->getAttribute('middleware-trace')));
    }

    #[Route('/kernel/middleware')]
    #[Middleware(new Trace('route'))]
    public function middleware(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->plaintext(implode(',', $request->getAttribute('middleware-trace')));
    }

    #[Route('/kernel/psr-response')]
    public function psrResponse(): ResponseInterface
    {
        return new Psr7Response(202, ['X-Response' => 'psr'], 'accepted');
    }

    #[Route('/kernel/server-error')]
    public function serverError(): Controller\Response
    {
        throw new RuntimeException('internal secret');
    }
}
