<?php

namespace GustavPHP\Tests\Integration\DTO;

final class CircularOutput
{
    public ?self $next = null;

    public function __construct(public string $name)
    {
    }
}
