<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Listener
{
    public function __construct(public int $priority = 0)
    {
    }
}
