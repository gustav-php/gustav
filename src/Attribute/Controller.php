<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Controller
{
    public function __construct(public string $path = '')
    {
    }
}
