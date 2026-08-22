<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Validation\Validation;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Validate
{
    public function __construct(public Validation $rule)
    {
    }
}
