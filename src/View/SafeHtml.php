<?php

namespace GustavPHP\Gustav\View;

use Stringable;

final readonly class SafeHtml implements Stringable
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
