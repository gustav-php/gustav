<?php

namespace GustavPHP\Tests\Integration\Services;

use Psr\Http\Message\ServerRequestInterface;

class RequestState
{
    public readonly int $id;
    private static int $nextId = 0;

    public function __construct(public readonly ServerRequestInterface $request)
    {
        $this->id = ++self::$nextId;
    }
}
