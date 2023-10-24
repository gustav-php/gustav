<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\Validation;

class Nullable extends Validation
{
    public function __construct(private readonly Validation $validator)
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
