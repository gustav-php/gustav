<?php

namespace GustavPHP\Gustav\View;

use GustavPHP\Gustav\View;

interface ViewRendererInterface
{
    public function render(View $view): string;
}
