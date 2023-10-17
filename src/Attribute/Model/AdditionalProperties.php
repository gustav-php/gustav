<?php

namespace GustavPHP\Gustav\Attribute\Model;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AdditionalProperties
{
    public function __construct(public bool $additionalProperties)
    {
    }
}
