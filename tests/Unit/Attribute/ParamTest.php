<?php

use GustavPHP\Gustav\Attribute\Param;

it('describes either one path parameter or the full parameter map', function () {
    $named = new Param('id');
    $all = new Param();

    expect($named->hasName())->toBeTrue()
        ->and($named->getName())->toBe('id')
        ->and($all->hasName())->toBeFalse();
});
