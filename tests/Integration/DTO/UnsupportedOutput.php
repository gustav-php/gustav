<?php

namespace GustavPHP\Tests\Integration\DTO;

use Closure;

final readonly class UnsupportedOutput
{
    public Closure $callback;

    public function __construct()
    {
        $this->callback = static fn (): string => 'not serializable';
    }
}
