<?php

namespace GustavPHP\Example\Middlewares;

use GustavPHP\Gustav\Context;
use GustavPHP\Gustav\Logger\Logger;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Message\ResponseInterface;
use GustavPHP\Gustav\Middleware;

class Logs extends Middleware\Base
{
    public function __construct()
    {
    }

    public function handle(RequestInterface $request, ResponseInterface $response): void
    {
        Logger::log('Request: ' . $request->getUrl());
    }
}
