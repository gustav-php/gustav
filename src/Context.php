<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\ControllerFactory;

class Context
{
    public function __construct(
        public string $path,
        public ?Route $route,
        public ?ControllerFactory $controllerFactory
    ) {
    }
}
