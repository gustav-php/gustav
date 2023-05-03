<?php

namespace GustavPHP\Gustav\Middleware;

use GustavPHP\Gustav\Context;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Message\ResponseInterface;

abstract class Base
{
    abstract public function __construct();
    abstract public function handle(RequestInterface $request, ResponseInterface $response, Context $context): void;
}
