<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\ControllerFactory;
use GustavPHP\Gustav\Service\Container;

class Context
{
    public function __construct(
        public Container $container,
        public string $path,
        public ?Route $route,
        public ?ControllerFactory $controllerFactory
    ) {
    }
}
