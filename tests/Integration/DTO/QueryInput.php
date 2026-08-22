<?php

namespace GustavPHP\Tests\Integration\DTO;

final readonly class QueryInput
{
    public function __construct(
        public string $term,
        public int $page,
        public bool $archived = false,
        public InputStatus $status = InputStatus::Draft,
    ) {
    }
}
