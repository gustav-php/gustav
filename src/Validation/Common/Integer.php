<?php

namespace GustavPHP\Gustav\Validation\Common;

use Exception;
use GustavPHP\Gustav\Validation\Validation;
use InvalidArgumentException;

class Integer extends Validation
{
    public function __construct(
        protected int $min = PHP_INT_MIN,
        protected int $max = PHP_INT_MAX
    ) {
        if ($this->min > $this->max) {
            throw new InvalidArgumentException("min must be less than max");
        }
        if ($this->min < PHP_INT_MIN) {
            throw new InvalidArgumentException("min must be greater than or equal to PHP_INT_MIN");
        }
        if ($this->max > PHP_INT_MAX) {
            throw new InvalidArgumentException("max must be less than or equal to PHP_INT_MAX");
        }
    }
    public function validate(mixed $value): true
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            throw new Exception("value must be integer");
        }
        if ($value < $this->min) {
            throw new Exception("value must be greater than or equal to {$this->min}");
        }
        if ($value > $this->max) {
            throw new Exception("value must be less than or equal to {$this->max}");
        }

        return true;
    }
}
