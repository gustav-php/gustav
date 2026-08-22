<?php

namespace GustavPHP\Tests\Integration\Middleware;

class ControllerTrace extends Trace
{
    public function __construct()
    {
        parent::__construct('controller');
    }
}
