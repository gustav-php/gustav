<?php

namespace GustavPHP\Tests\Integration\Services;

use GustavPHP\Gustav\Attribute\Service;
use Psr\Http\Message\ServerRequestInterface;

#[Service]
class RequestState
{
    public readonly int $id;
    private static int $nextId = 0;

    public function __construct(public readonly ServerRequestInterface $request)
    {
        $this->id = ++self::$nextId;
    }
}
