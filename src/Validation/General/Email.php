<?php

namespace GustavPHP\Gustav\Validation\General;

use GustavPHP\Gustav\Validation\Validation;

class Email extends Validation
{
    public function validate(mixed $value): true
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email address");
        }
        return true;
    }
}
