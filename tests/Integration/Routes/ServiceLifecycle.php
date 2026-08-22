<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Middleware, Request, Route};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Tests\Integration\Middleware\Injected;
use GustavPHP\Tests\Integration\Services\{Greeting, RequestState, SingletonState, TransientState};
use Psr\Http\Message\ServerRequestInterface;

class ServiceLifecycle extends Controller\Base
{
    public function __construct(
        private readonly Greeting $greeting,
        private readonly RequestState $requestState,
        private readonly SingletonState $singletonState,
        private readonly TransientState $firstTransient,
        private readonly TransientState $secondTransient,
    ) {
    }

    #[Route('/services/lifecycle/error')]
    public function error(): Controller\Response
    {
        throw new HttpException(418, (string) $this->requestState->id);
    }

    /**
     * @return array<string, bool|int|string>
     */
    #[Route('/services/lifecycle')]
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
        ];
    }
}
