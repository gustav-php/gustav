<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Option
{
    public function __construct(
        public ?string $name = null,
        public ?string $shortcut = null,
        public string $description = '',
    ) {
        if ($name !== null && !preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            throw new InvalidArgumentException("Command option name '{$name}' is invalid");
        }
        if ($shortcut !== null && !preg_match('/^[a-zA-Z0-9]$/', $shortcut)) {
            throw new InvalidArgumentException("Command option shortcut '{$shortcut}' is invalid");
        }
    }
}
