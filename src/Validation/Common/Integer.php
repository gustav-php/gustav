<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};
use InvalidArgumentException;

class Integer extends Validation
{
    public function __construct(
        protected int $min = PHP_INT_MIN,
        protected int $max = PHP_INT_MAX
    ) {
        if ($this->min > $this->max) {
            throw new InvalidArgumentException('min must be less than or equal to max');
        }
        if ($this->min < PHP_INT_MIN) {
            throw new InvalidArgumentException('min must be greater than or equal to PHP_INT_MIN');
        }
        if ($this->max > PHP_INT_MAX) {
            throw new InvalidArgumentException('max must be less than or equal to PHP_INT_MAX');
        }
    }
    public function getViolation(mixed $value): ?RuleViolation
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false) {
            return new RuleViolation('invalid_integer', 'Value must be integer');
        }
        if ($integer < $this->min) {
            return new RuleViolation('min_value', "Value must be greater than or equal to {$this->min}");
        }
        if ($integer > $this->max) {
            return new RuleViolation('max_value', "Value must be less than or equal to {$this->max}");
        }

        return null;
    }
}
