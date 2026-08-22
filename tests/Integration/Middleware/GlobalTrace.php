<?php

namespace GustavPHP\Tests\Integration\Middleware;

class GlobalTrace extends Trace
{
    public function __construct()
    {
        parent::__construct('global');
    }
}
