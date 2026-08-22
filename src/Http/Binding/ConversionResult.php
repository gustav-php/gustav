<?php

namespace GustavPHP\Gustav\Http\Binding;

use GustavPHP\Gustav\Validation\Violation;

final readonly class ConversionResult
{
    /**
     * @param list<Violation> $violations
     */
    private function __construct(
        public bool $isValid,
        public mixed $value,
        public array $violations,
    ) {
    }

    public static function failure(Violation $violation): self
    {
        return new self(false, null, [$violation]);
    }

    /**
     * @param list<Violation> $violations
     */
    public static function failures(array $violations): self
    {
        return new self(false, null, $violations);
    }

    public static function success(mixed $value): self
    {
        return new self(true, $value, []);
    }
}
