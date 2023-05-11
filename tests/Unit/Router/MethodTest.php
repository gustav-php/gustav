<?php

use GustavPHP\Gustav\Message\SAPI\Request;
use GustavPHP\Gustav\Router\Method;

it('can match method', function (string $method) {
    $request = new Request();
    $request->setMethod($method);
    $value = Method::fromRequest($request);
    expect($value)->toBeInstanceOf(Method::class);
    expect($value->value)->toBe($method);
})->with(['GET', 'DELETE', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS', 'CONNECT', 'TRACE']);
