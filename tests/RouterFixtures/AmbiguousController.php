<?php

namespace GustavPHP\Tests\RouterFixtures;

use GustavPHP\Gustav\Attribute\{Controller, Get, Param};

#[Controller]
final class AmbiguousController
{
    /** @return array{} */
    #[Get('/fixed/{value}')]
    public function first(#[Param('value')] string $value): array
    {
        return [];
    }

    /** @return array{} */
    #[Get('/{value}/fixed')]
    public function second(#[Param('value')] string $value): array
    {
        return [];
    }
}
