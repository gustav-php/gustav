<?php

namespace GustavPHP\Gustav\Http\Binding;

use GustavPHP\Gustav\Http\Binding\Resolver\InputResolver;
use GustavPHP\Gustav\Validation\Validation;

final readonly class ArgumentMetadata
{
    /**
     * @param list<Validation> $rules
     */
    public function __construct(
        public string $name,
        public InputResolver $resolver,
        public ?TypeConverter $converter,
        public bool $hasDefault,
        public array $rules,
    ) {
    }
}
