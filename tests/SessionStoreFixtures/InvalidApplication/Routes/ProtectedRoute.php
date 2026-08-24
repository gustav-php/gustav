<?php

namespace GustavPHP\Tests\SessionStoreFixtures\InvalidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Csrf, Post};

#[Controller]
final class ProtectedRoute
{
    /** @return array{ok:true} */
    #[Csrf]
    #[Post('/protected')]
    public function store(): array
    {
        return ['ok' => true];
    }
}
