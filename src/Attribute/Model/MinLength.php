<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MinLength extends Validator
{
    public function __construct(protected string $minimum)
    {
    }
    public function __invoke(string $value): bool
    {
        return $this->minimum <= \mb_strlen($value);
    }
}
