<?php

namespace GustavPHP\Gustav\Router;

use GustavPHP\Gustav\Http\Binding\RequestBinder;
use GustavPHP\Gustav\Http\ResponseHandler;
use Psr\Http\Server\MiddlewareInterface;

/** @internal */
final readonly class RouteDefinition
{
    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>> $middlewares
     */
    public function __construct(
        public RoutePath $path,
        public Method $method,
        public ?string $name,
        public string $controller,
        public string $handler,
        public RequestBinder $requestBinder,
        public ResponseHandler $responseHandler,
        public array $middlewares,
        public bool $csrfProtected,
    ) {
    }

    public function location(): string
    {
        return "{$this->controller}::{$this->handler}()";
    }
}
