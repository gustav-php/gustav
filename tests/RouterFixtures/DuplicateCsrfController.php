<?php

namespace GustavPHP\Tests\RouterFixtures;

use GustavPHP\Gustav\Attribute\{Controller, Csrf, Post};

#[Controller]
final class DuplicateCsrfController
{
    /** @return array{} */
    #[Csrf]
    #[Csrf]
    #[Post('/duplicate-csrf')]
    public function store(): array
    {
        return [];
    }
}
