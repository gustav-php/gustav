<?php

namespace GustavPHP\Gustav\DTO;

use GustavPHP\Gustav\Input\TypeConverter;
use GustavPHP\Gustav\Validation\Validation;
use ReflectionProperty;

final readonly class DtoField
{
    /**
     * @param list<Validation> $rules
     */
    public function __construct(
        public string $name,
        public TypeConverter $converter,
        public bool $hasDefault,
        public array $rules,
        public ?ReflectionProperty $property = null,
    ) {
    }
}
