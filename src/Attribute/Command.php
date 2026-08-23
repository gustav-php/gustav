<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Command
{
    public function __construct(
        public string $name,
        public string $description = '',
        public bool $hidden = false,
    ) {
        if (!preg_match('/^[a-z][a-z0-9-]*(?::[a-z][a-z0-9-]*)*$/', $name)) {
            throw new InvalidArgumentException("Command name '{$name}' is invalid");
        }
    }
}
