<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Format extends Validator
{
    public function __construct(protected string $format)
    {
    }
    public function __invoke(string $value): bool
    {
        return \preg_match($this->format, $value) === 1;
    }
}
