<?php

namespace GustavPHP\Tests\Integration\DTO;

use GustavPHP\Gustav\Attribute\Validate;
use GustavPHP\Gustav\Validation\Common\{Email, Integer};

final readonly class BodyInput
{
    public function __construct(
        #[Validate(new Email())]
        public string $email,
        #[Validate(new Integer(min: 0, max: 120))]
        public int $age,
        public bool $active,
        public InputStatus $status,
        public ?string $nickname,
        public string $label = 'default-label',
    ) {
    }
}
