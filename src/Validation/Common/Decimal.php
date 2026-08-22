<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};
use InvalidArgumentException;

class Decimal extends Validation
{
    public function __construct(
        protected float $min = -PHP_FLOAT_MAX,
        protected float $max = PHP_FLOAT_MAX
    ) {
        if ($this->min > $this->max) {
            throw new InvalidArgumentException('min must be less than or equal to max');
        }
        if (!is_finite($this->min) || !is_finite($this->max)) {
            throw new InvalidArgumentException('min and max must be finite');
        }
    }

    public function getViolation(mixed $value): ?RuleViolation
    {
        $decimal = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($decimal === false || !is_finite($decimal)) {
            return new RuleViolation('invalid_decimal', 'Value must be decimal');
        }
        if ($decimal < $this->min) {
            return new RuleViolation('min_value', "Value must be greater than or equal to {$this->min}");
        }
        if ($decimal > $this->max) {
            return new RuleViolation('max_value', "Value must be less than or equal to {$this->max}");
        }

        return null;
    }
}
