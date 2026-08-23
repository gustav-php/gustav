<?php

use GustavPHP\Gustav\Router\RouteCompiler;
use GustavPHP\Tests\Fixtures\{AmbiguousInputController, MultipleInputController};

it('rejects ambiguous input unions during route compilation', function () {
    RouteCompiler::compile(AmbiguousInputController::class);
})->throws(LogicException::class, 'ambiguous unions');

it('requires exactly one source attribute during route compilation', function () {
    RouteCompiler::compile(MultipleInputController::class);
})->throws(LogicException::class, 'exactly one request input attribute');
