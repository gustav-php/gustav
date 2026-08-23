<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Gustav\View;

#[Controller]
final class NullableViewController
{
    #[Get]
    public function index(): ?View
    {
        return null;
    }
}
