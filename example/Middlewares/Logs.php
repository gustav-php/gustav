<?php

namespace GustavPHP\Example\Middlewares;

use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Logger\Logger;
use GustavPHP\Gustav\Middleware;
use Psr\Http\Message\ServerRequestInterface;

class Logs extends Middleware\Base
{
    public function __construct()
    {
    }

    public function handle(ServerRequestInterface $request, Response $response): void
    {
        Logger::log('Request: ' . $request->getUri());
    }
}
