<?php

namespace GustavPHP\Gustav\Traits;

use GustavPHP\Gustav\Http\Exception\ValidationException;
use GustavPHP\Gustav\Validation\{Validation, Violation};

trait Validate
{
    /**
     * @param array<array{0:mixed,1:Validation,2?:string}> $entries
     */
    protected function validate(array $entries): void
    {
        $violations = [];

        foreach ($entries as $index => $entry) {
            [$value, $validation] = $entry;
            $violation = $validation->getViolation($value);
            if ($violation !== null) {
                $violations[] = new Violation(
                    'controller',
                    $entry[2] ?? (string) $index,
                    $violation->code,
                    $violation->message,
                );
            }
        }

        if ($violations !== []) {
            throw new ValidationException($violations);
        }
    }
}
