<?php

namespace GustavPHP\Gustav\Validation\General;

use GustavPHP\Gustav\Validation\Validation;

class Boolean extends Validation
{
    public function validate(mixed $value): true
    {
        if ($value === true || $value === false || $value === "true" || $value === "false") {
            return true;
        }

        throw new \Exception("value must be boolean");
    }
}
