<?php

namespace GustavPHP\Tests\ViewRendererFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Gustav\View;

#[Controller]
final readonly class Page
{
    #[Get]
    public function index(): View
    {
        return new View(
            template: 'custom-home',
            data: ['name' => 'Gustav'],
            status: 202,
            headers: ['X-Renderer' => 'custom'],
        );
    }
}
