<?php

use GustavPHP\Gustav\View;

it('stores immutable view response metadata', function () {
    $model = new stdClass();
    $view = new View(
        template: 'home',
        data: $model,
        status: 202,
        headers: ['X-View' => 'native'],
    );

    expect($view->template)->toBe('home')
        ->and($view->data)->toBe($model)
        ->and($view->status)->toBe(202)
        ->and($view->headers)->toBe(['X-View' => 'native']);
});

it('rejects invalid view response metadata', function (string $template, int $status, string $message) {
    expect(fn () => new View($template, status: $status))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty template' => ['', 200, 'View template must be a non-empty logical name'],
    'invalid status' => ['home', 99, 'View response status must be between 100 and 599'],
]);
