<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Input\TypeConverter;
use GustavPHP\Gustav\Validation\Validation;

/** @internal */
final readonly class ParameterMetadata
{
    /**
     * @param list<Validation> $rules
     */
    public function __construct(
        public string $parameterName,
        public string $inputName,
        public InputKind $kind,
        public string $description,
        public ?string $shortcut,
        public TypeConverter $converter,
        public bool $hasDefault,
        public mixed $defaultValue,
        public array $rules,
    ) {
    }
}
