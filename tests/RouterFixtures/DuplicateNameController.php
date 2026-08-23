<?php

namespace GustavPHP\Tests\RouterFixtures;

use GustavPHP\Gustav\Attribute\{Controller, Get};

#[Controller]
final class DuplicateNameController
{
    /** @return array{} */
    #[Get('/first', name: 'duplicate')]
    public function first(): array
    {
        return [];
    }

    /** @return array{} */
    #[Get('/second', name: 'duplicate')]
    public function second(): array
    {
        return [];
    }
}
