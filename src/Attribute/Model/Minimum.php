<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Minimum extends Validator
{
    public function __construct(protected int|float $minimum)
    {
    }
    public function __invoke(int|float $value): bool
    {
        return $this->minimum <= $value;
    }
}
