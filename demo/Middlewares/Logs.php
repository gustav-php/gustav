<?php

namespace GustavPHP\Demo\Middlewares;

use GustavPHP\Gustav\Logger\Logger;
use GustavPHP\Gustav\Middleware;
use Psr\Http\Message\ServerRequestInterface;

class Logs extends Middleware\Base
{
    public function __construct()
    {
    }

    public function handle(ServerRequestInterface $request): ServerRequestInterface
    {
        Logger::log('Request: ' . $request->getUri()->getPath());

        return $request;
    }
}
