<?php

use GustavPHP\Gustav\Router\RouteCompiler;
use GustavPHP\Tests\Fixtures\InvalidAuthController;

it('requires auth user parameters to implement Identity', function () {
    RouteCompiler::compile(InvalidAuthController::class);
})->throws(LogicException::class, 'must implement');
