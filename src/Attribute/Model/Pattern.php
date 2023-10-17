<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Pattern extends Validator
{
    public function __construct(protected callable $callback)
    {
    }
    public function __invoke(mixed $value): bool
    {
        return ($this->callback)($value);
    }
}
