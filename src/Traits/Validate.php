<?php

namespace GustavPHP\Gustav\Traits;

use GustavPHP\Gustav\Validation\Validation;

trait Validate
{
    /**
     * @param array<array{mixed,Validation}> $entries
     * @return void
     */
    protected function validate(array $entries): void
    {
        foreach ($entries as [$value, $validation]) {
            $validation->validate($value);
        }
    }
}
