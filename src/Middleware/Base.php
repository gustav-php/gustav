<?php

namespace TorstenDittmann\Gustav\Middleware;

use TorstenDittmann\Gustav\Context;
use TorstenDittmann\Gustav\Message\RequestInterface;
use TorstenDittmann\Gustav\Message\ResponseInterface;

abstract class Base
{
    abstract public function __construct();
    abstract public function handle(RequestInterface $request, ResponseInterface $response, Context $context): void;
}
