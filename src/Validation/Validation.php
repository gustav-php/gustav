<?php

namespace GustavPHP\Gustav\Validation;

use GustavPHP\Gustav\Http\Exception\ValidationException;

abstract class Validation
{
    abstract public function getViolation(mixed $value): ?RuleViolation;

    public function validate(mixed $value): true
    {
        $violation = $this->getViolation($value);
        if ($violation !== null) {
            throw new ValidationException([
                new Violation('validation', 'value', $violation->code, $violation->message),
            ]);
        }

        return true;
    }
}
