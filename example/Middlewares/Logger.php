<?php

namespace GustavPHP\Example\Middlewares;

use GustavPHP\Gustav\Context;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Message\ResponseInterface;
use GustavPHP\Gustav\Middleware;

class Logger extends Middleware\Base
{
    public function __construct()
    {
    }

    public function handle(RequestInterface $request, ResponseInterface $response, Context $context): void
    {
        \error_log('# request incoming');
    }
}
