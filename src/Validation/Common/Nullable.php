<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};

class Nullable extends Validation
{
    public function __construct(private readonly Validation $validator)
    {
    }

    public function getViolation(mixed $value): ?RuleViolation
    {
        if ($value === null) {
            return null;
        }

        return $this->validator->getViolation($value);
    }
}
