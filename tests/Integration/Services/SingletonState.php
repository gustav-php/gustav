<?php

namespace GustavPHP\Tests\Integration\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;

#[Service(lifetime: Lifetime::Singleton)]
class SingletonState
{
    public readonly int $id;
    private static int $nextId = 0;

    public function __construct()
    {
        $this->id = ++self::$nextId;
    }
}
