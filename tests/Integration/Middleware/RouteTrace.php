<?php

namespace GustavPHP\Tests\Integration\Middleware;

class RouteTrace extends Trace
{
    public function __construct()
    {
        parent::__construct('route');
    }
}
