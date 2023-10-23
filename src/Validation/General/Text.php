<?php

namespace GustavPHP\Gustav\Validation\General;

use GustavPHP\Gustav\Validation\Validation;

class Text extends Validation
{
    public function __construct(
        protected int $minLength = null,
        protected int $maxLength = null
    ) {
        if ($this->minLength !== null && $this->minLength < 0) {
            throw new \InvalidArgumentException("minLength must be greater than 0");
        }
        if ($this->maxLength !== null) {
            if ($this->maxLength < 0) {
                throw new \InvalidArgumentException("maxLength must be greater than 0");
            }
            if ($this->minLength !== null && $this->minLength > $this->maxLength) {
                throw new \InvalidArgumentException("minLength must be less than maxLength");
            }
        }
    }
    public function validate(mixed $value): true
    {
        if (!is_string($value)) {
            throw new \Exception("value must be string");
        }

        $length = mb_strlen($value);
        if ($this->minLength !== null && $length < $this->minLength) {
            throw new \Exception("value must be longer than {$this->minLength}");
        }
        if ($this->maxLength !== null && $length > $this->maxLength) {
            throw new \Exception("value must be shorter than {$this->maxLength}");
        }

        return true;
    }
}
