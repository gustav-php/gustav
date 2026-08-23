<?php

namespace GustavPHP\Gustav\Router;

/** @internal */
final readonly class RouteMatch
{
    /** @param array<string,string> $parameters */
    public function __construct(
        public RouteDefinition $route,
        public array $parameters,
    ) {
    }
}
