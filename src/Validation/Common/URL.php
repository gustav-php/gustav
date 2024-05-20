<?php

namespace GustavPHP\Gustav\Validation\Common;

use Exception;
use GustavPHP\Gustav\Validation\Validation;

class URL extends Validation
{
    public function validate(mixed $value): true
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
        }
        return true;
    }
}
