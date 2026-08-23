<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{AuthUser, Middleware, Request, Route};
use GustavPHP\Gustav\Auth\{AuthenticationMiddleware, Identity};
use GustavPHP\Gustav\Auth\Exception\ForbiddenException;
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Gustav\Http\RequestId;
use GustavPHP\Tests\Integration\Middleware\{Block, ControllerTrace, Legacy, RouteTrace};
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use RuntimeException;

#[Middleware(ControllerTrace::class)]
class Kernel extends Controller\Base
{
    public function __construct(private readonly RequestId $requestId)
    {
    }

    #[Route('/kernel/auth')]
    #[Middleware(AuthenticationMiddleware::class)]
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
    #[Middleware(Block::class)]
    public function blocked(): Controller\Response
    {
        return $this->plaintext('controller should not run');
    }

    #[Route('/kernel/coded-server-error')]
    public function codedServerError(): Controller\Response
    {
        throw new RuntimeException('coded internal secret', 422);
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
    #[Middleware(Legacy::class)]
    public function legacy(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->plaintext(implode(',', $request->getAttribute('middleware-trace')));
    }

    #[Route('/kernel/middleware')]
    #[Middleware(RouteTrace::class)]
    public function middleware(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->plaintext(implode(',', $request->getAttribute('middleware-trace')));
    }

    #[Route('/kernel/psr-response')]
    public function psrResponse(): ResponseInterface
    {
        return new Psr7Response(202, ['X-Response' => 'psr'], 'accepted');
    }

    #[Route('/kernel/request-id')]
    public function requestId(#[Request] ServerRequestInterface $request): Controller\Response
    {
        $attribute = $request->getAttribute(RequestId::ATTRIBUTE);

        return $this->json([
            'requestId' => (string) $this->requestId,
            'attribute' => $attribute instanceof RequestId ? (string) $attribute : null,
        ]);
    }

    #[Route('/kernel/request-id-override')]
    public function requestIdOverride(): Controller\Response
    {
        return $this->plaintext('ok', headers: ['X-Request-ID' => 'controller-value']);
    }

    #[Route('/kernel/server-error')]
    public function serverError(): Controller\Response
    {
        throw new RuntimeException('internal secret');
    }

    #[Route('/kernel/unavailable')]
    public function unavailable(): Controller\Response
    {
        throw new HttpException(503, 'Dependency unavailable');
    }
}
