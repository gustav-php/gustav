<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};

class Boolean extends Validation
{
    public function getViolation(mixed $value): ?RuleViolation
    {
        if (in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
            return null;
        }

        return new RuleViolation('invalid_boolean', 'Value must be boolean');
    }
}
