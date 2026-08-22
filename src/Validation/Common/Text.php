<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};
use InvalidArgumentException;

class Text extends Validation
{
    public function __construct(
        protected ?int $minLength = null,
        protected ?int $maxLength = null
    ) {
        if ($this->minLength !== null && $this->minLength < 0) {
            throw new InvalidArgumentException('minLength must be greater than or equal to 0');
        }
        if ($this->maxLength !== null) {
            if ($this->maxLength < 0) {
                throw new InvalidArgumentException('maxLength must be greater than or equal to 0');
            }
            if ($this->minLength !== null && $this->minLength > $this->maxLength) {
                throw new InvalidArgumentException('minLength must be less than or equal to maxLength');
            }
        }
    }
    public function getViolation(mixed $value): ?RuleViolation
    {
        if (!is_string($value)) {
            return new RuleViolation('invalid_string', 'Value must be string');
        }

        $length = mb_strlen($value);
        if ($this->minLength !== null && $length < $this->minLength) {
            return new RuleViolation('min_length', "Value must contain at least {$this->minLength} characters");
        }
        if ($this->maxLength !== null && $length > $this->maxLength) {
            return new RuleViolation('max_length', "Value must contain at most {$this->maxLength} characters");
        }

        return null;
    }
}
