<?php

namespace GustavPHP\Tests\ConfigurationFixtures\Unit;

use GustavPHP\Gustav\Attribute\{Config, Env, Validate};
use GustavPHP\Gustav\Validation\Common\{Email, Integer};

#[Config]
final readonly class MultipleInvalidConfig
{
    public function __construct(
        #[Env('APP_PORT'), Validate(new Integer(min: 1, max: 65535))]
        public int $port,
        #[Env('APP_EMAIL'), Validate(new Email())]
        public string $email,
        #[Env('APP_STAGE')]
        public Stage $stage,
        #[Env('APP_TOKEN')]
        public string $token,
    ) {
    }
}
