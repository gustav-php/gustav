<?php

namespace GustavPHP\Gustav\Middleware;

use Psr\Http\Message\ServerRequestInterface;

abstract class Base
{
    abstract public function __construct();
    abstract public function handle(ServerRequestInterface $request): ServerRequestInterface;
}
