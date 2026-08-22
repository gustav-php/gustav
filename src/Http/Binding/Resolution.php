<?php

namespace GustavPHP\Gustav\Http\Binding;

final readonly class Resolution
{
    private function __construct(
        public bool $isPresent,
        public mixed $value = null,
    ) {
    }

    public static function missing(): self
    {
        return new self(false);
    }

    public static function present(mixed $value): self
    {
        return new self(true, $value);
    }
}
