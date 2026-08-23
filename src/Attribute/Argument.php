<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Argument
{
    public function __construct(
        public ?string $name = null,
        public string $description = '',
    ) {
        if ($name !== null && !preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            throw new InvalidArgumentException("Command argument name '{$name}' is invalid");
        }
    }
}
