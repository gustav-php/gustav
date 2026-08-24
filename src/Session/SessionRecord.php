<?php

namespace GustavPHP\Gustav\Session;

final readonly class SessionRecord
{
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $flash
     */
    public function __construct(
        public array $data,
        public array $flash,
        public int $expiresAt,
    ) {
    }
}
