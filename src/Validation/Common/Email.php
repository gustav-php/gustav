<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};

class Email extends Validation
{
    public function getViolation(mixed $value): ?RuleViolation
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return new RuleViolation('invalid_email', 'Email is invalid');
        }

        return null;
    }
}
