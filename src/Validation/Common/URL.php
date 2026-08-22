<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};

class URL extends Validation
{
    public function getViolation(mixed $value): ?RuleViolation
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return new RuleViolation('invalid_url', 'URL is invalid');
        }

        return null;
    }
}
