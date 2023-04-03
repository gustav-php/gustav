<?php

namespace TorstenDittmann\Gustav\Middleware;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use TorstenDittmann\Gustav\Context;

abstract class Base
{
    abstract public function __construct();
    abstract public function handle(Request $request, Response $response, Context $context): void;
}
