<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Env
{
    public function __construct(public string $name)
    {
        if ($name === '' || str_contains($name, '=') || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Environment variable name is invalid');
        }
    }
}
