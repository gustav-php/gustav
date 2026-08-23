<?php

namespace GustavPHP\Tests\ViewRendererFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Gustav\View;
use GustavPHP\Gustav\View\ViewRendererInterface;

#[Service(as: ViewRendererInterface::class, lifetime: Lifetime::Singleton)]
final class CustomViewRenderer implements ViewRendererInterface
{
    public function render(View $view): string
    {
        $name = is_array($view->data) ? $view->data['name'] ?? '' : '';

        return "custom:{$view->template}:{$name}";
    }
}
