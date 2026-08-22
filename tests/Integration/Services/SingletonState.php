<?php

namespace GustavPHP\Tests\Integration\Services;

class SingletonState
{
    public readonly int $id;
    private static int $nextId = 0;

    public function __construct()
    {
        $this->id = ++self::$nextId;
    }
}
