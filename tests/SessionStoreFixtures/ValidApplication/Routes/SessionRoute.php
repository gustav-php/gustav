<?php

namespace GustavPHP\Tests\SessionStoreFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Gustav\Session;

#[Controller]
final class SessionRoute
{
    public function __construct(private readonly Session $session)
    {
    }

    /** @return array{visits:int} */
    #[Get('/session-store')]
    public function show(): array
    {
        $visits = (int) $this->session->get('visits', 0) + 1;
        $this->session->put('visits', $visits);

        return ['visits' => $visits];
    }
}
