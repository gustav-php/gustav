<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Controller\Response;
use Psr\Http\Message\ServerRequestInterface;

abstract class Base
{
    abstract public function __construct();
    abstract public function handle(ServerRequestInterface $request, Response $response): void;
}
