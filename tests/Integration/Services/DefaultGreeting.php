<?php

namespace GustavPHP\Tests\Integration\Services;

class DefaultGreeting implements Greeting
{
    public function message(): string
    {
        return 'configured';
    }
}
