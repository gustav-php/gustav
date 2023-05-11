<?php

use GustavPHP\Gustav\Attribute\Route;

it('can set and get property', function (string $property, mixed $value) {
    $instance = new Route('/path');
    expect($instance->{'set' . ucfirst($property)}($value))->toBe($instance);
    expect($instance->{'get' . ucfirst($property)}())->toBe($value);
})->with([['class', 'test'], ['function', 'test']]);
