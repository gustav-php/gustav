<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get, Middleware, Request};
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Gustav\Router\UrlGeneratorInterface;
use GustavPHP\Tests\Integration\Middleware\Injected;
use GustavPHP\Tests\Integration\Services\{Greeting, RequestState, SingletonState, TransientState};
use Psr\Http\Message\ServerRequestInterface;

#[Controller('/services/lifecycle')]
class ServiceLifecycle
{
    public function __construct(
        private readonly Greeting $greeting,
        private readonly RequestState $requestState,
        private readonly SingletonState $singletonState,
        private readonly TransientState $firstTransient,
        private readonly TransientState $secondTransient,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    #[Get('/error')]
    public function error(): Response
    {
        throw new HttpException(418, (string) $this->requestState->id);
    }

    /**
     * @return array<string, bool|int|string>
     */
    #[Get(name: 'services.lifecycle')]
    #[Middleware(Injected::class)]
    public function lifecycle(#[Request] ServerRequestInterface $request): array
    {
        return [
            'greeting' => $this->greeting->message(),
            'request' => $this->requestState->id,
            'middleware' => $request->getAttribute('middleware-service'),
            'singleton' => $this->singletonState->id,
            'transientsDiffer' => $this->firstTransient !== $this->secondTransient,
            'path' => $this->requestState->request->getUri()->getPath(),
            'url' => $this->urls->generate('services.lifecycle', query: ['source' => 'test']),
        ];
    }
}
