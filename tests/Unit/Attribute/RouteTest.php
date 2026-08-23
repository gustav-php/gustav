<?php

use GustavPHP\Gustav\Attribute\{Controller, Delete, Get, Head, Options, Patch, Post, Put, Route};
use GustavPHP\Gustav\Router\Method;

it('keeps route attributes as immutable metadata', function () {
    $route = new Route('/path', Method::POST, 'path.create');

    expect($route->getPath())->toBe('/path')
        ->and($route->getMethod())->toBe(Method::POST)
        ->and($route->getName())->toBe('path.create');
});

it('provides concise HTTP method attributes', function (Route $route, Method $method) {
    expect($route->getPath())->toBe('/path')
        ->and($route->getMethod())->toBe($method)
        ->and($route->getName())->toBe('route.name');
})->with([
    'delete' => [new Delete('/path', 'route.name'), Method::DELETE],
    'get' => [new Get('/path', 'route.name'), Method::GET],
    'head' => [new Head('/path', 'route.name'), Method::HEAD],
    'options' => [new Options('/path', 'route.name'), Method::OPTIONS],
    'patch' => [new Patch('/path', 'route.name'), Method::PATCH],
    'post' => [new Post('/path', 'route.name'), Method::POST],
    'put' => [new Put('/path', 'route.name'), Method::PUT],
]);

it('stores a controller route prefix', function () {
    expect((new Controller('/users'))->path)->toBe('/users');
});
