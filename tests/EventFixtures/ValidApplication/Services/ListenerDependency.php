<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Services;

final readonly class ListenerDependency
{
    public int $id;

    public function __construct()
    {
        static $next = 0;

        $this->id = ++$next;
    }
}
