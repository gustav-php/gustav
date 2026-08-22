<?php

namespace GustavPHP\Gustav\Http\Exception;

use GustavPHP\Gustav\Validation\Violation;
use InvalidArgumentException;

final class ValidationException extends RequestInputException
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(private readonly array $violations)
    {
        if ($violations === []) {
            throw new InvalidArgumentException('A validation exception requires at least one violation');
        }

        parent::__construct(422, 'Validation failed');
    }

    /**
     * @return list<Violation>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
