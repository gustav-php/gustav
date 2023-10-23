<?php

namespace GustavPHP\Gustav\Traits;

use GustavPHP\Gustav\Validation\Validation;

trait Validate
{
    /**
     * @param array<mixed,Validation> $values
     * @return void
     */
    public function validate(array $values): void
    {
        foreach ($values as $value => $validation) {
            $validation->validate($value);
        }
    }
}
