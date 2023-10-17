<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MaxLength extends Validator
{
    public function __construct(protected string $maximum)
    {
    }
    public function __invoke(string $value): bool
    {
        return \mb_strlen($value) <= $this->maximum;
    }
}
