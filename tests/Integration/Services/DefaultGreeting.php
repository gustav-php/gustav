<?php

namespace GustavPHP\Tests\Integration\Services;

use GustavPHP\Gustav\Attribute\Service;

#[Service(as: Greeting::class)]
class DefaultGreeting implements Greeting
{
    public function message(): string
    {
        return 'configured';
    }
}
