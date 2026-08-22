<?php

namespace GustavPHP\Gustav\Validation;

final readonly class RuleViolation
{
    public function __construct(
        public string $code,
        public string $message,
    ) {
    }
}
