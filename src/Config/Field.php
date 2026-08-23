<?php

namespace GustavPHP\Gustav\Config;

use GustavPHP\Gustav\Validation\Validation;

/** @internal */
final readonly class Field
{
    /**
     * @param list<Validation> $rules
     */
    public function __construct(
        public string $property,
        public string $variable,
        public Converter $converter,
        public bool $hasDefault,
        public array $rules,
    ) {
    }
}
