<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Traits\{Logger, Validate};
use Psr\Http\Message\ServerRequestInterface;

abstract class Base
{
    use Logger;
    use Validate;

    abstract public function handle(ServerRequestInterface $request): ServerRequestInterface|Response;
}
