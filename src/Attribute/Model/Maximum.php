<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Maximum extends Validator
{
    public function __construct(protected int|float $maximum)
    {
    }
    public function __invoke(int|float $value): bool
    {
        return $value <= $this->maximum;
    }
}
