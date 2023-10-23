<?php

namespace GustavPHP\Gustav\Validation\General;

use GustavPHP\Gustav\Validation\Validation;

class Nullable extends Validation
{
    public function __construct(private Validation $validator)
    {
    }

    public function validate(mixed $value): true
    {
        if ($value === null) {
            return true;
        }

        return $this->validator->validate($value);
    }
}
