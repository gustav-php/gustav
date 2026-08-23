<?php

namespace GustavPHP\Tests\CommandFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Service;

#[Service]
final readonly class CommandState
{
    public int $id;

    public function __construct()
    {
        static $next = 0;

        $this->id = ++$next;
    }
}
