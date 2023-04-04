<?php

namespace TorstenDittmann\Example\Middlewares;

use TorstenDittmann\Gustav\Context;
use TorstenDittmann\Gustav\Message\RequestInterface;
use TorstenDittmann\Gustav\Message\ResponseInterface;
use TorstenDittmann\Gustav\Middleware;

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
