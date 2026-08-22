<?php

namespace GustavPHP\Tests\Integration\DTO;

use GustavPHP\Gustav\Attribute\Serializer\Exclude;

final readonly class OwnerOutput
{
    public function __construct(
        public string $name,
        #[Exclude]
        public string $token,
    ) {
    }
}
