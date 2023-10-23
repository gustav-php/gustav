<?php

namespace GustavPHP\Gustav\Validation\General;

use GustavPHP\Gustav\Validation\Validation;

class Decimal extends Validation
{
    public function __construct(
        protected float $min = PHP_FLOAT_MIN,
        protected float $max = PHP_FLOAT_MAX
    ) {
        if ($this->min > $this->max) {
            throw new \InvalidArgumentException("min must be less than max");
        }
        if ($this->min < PHP_FLOAT_MIN) {
            throw new \InvalidArgumentException("min must be greater than or equal to PHP_FLOAT_MIN");
        }
        if ($this->max > PHP_FLOAT_MAX) {
            throw new \InvalidArgumentException("max must be less than or equal to PHP_FLOAT_MAX");
        }
    }
    public function validate(mixed $value): true
    {
        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
            throw new \Exception("value must be integer");
        }
        if ($value < $this->min) {
            throw new \Exception("value must be greater than or equal to {$this->min}");
        }
        if ($value > $this->max) {
            throw new \Exception("value must be less than or equal to {$this->max}");
        }

        return true;
    }
}
