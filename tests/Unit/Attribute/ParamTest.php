<?php

use GustavPHP\Gustav\Attribute\Param;

it('can set and get property', function (string $property, mixed $value) {
    $instance = new Param('name');
    expect($instance->{'set' . ucfirst($property)}($value))->toBe($instance);
    expect($instance->{'get' . ucfirst($property)}())->toBe($value);
})->with([['parameter', 'test']]);
