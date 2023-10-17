<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Traits\Logger;
use Psr\Http\Message\ServerRequestInterface;

abstract class Base
{
    use Logger;

    abstract public function __construct();
    abstract public function handle(ServerRequestInterface $request): ServerRequestInterface;
}
