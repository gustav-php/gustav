<?php

use GustavPHP\Gustav\Attribute\Middleware;
use GustavPHP\Tests\Integration\Middleware\Block;

it('stores an injectable middleware class', function () {
    $middleware = new Middleware(Block::class);

    expect($middleware->getClass())->toBe(Block::class);
});

it('rejects classes that are not PSR middleware', function () {
    new Middleware(stdClass::class);
})->throws(InvalidArgumentException::class, 'must implement');
