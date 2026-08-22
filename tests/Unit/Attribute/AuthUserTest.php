<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Tests\Fixtures\InvalidAuthController;

it('requires auth user parameters to implement Identity', function () {
    $app = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\Empty',
        cache: sys_get_temp_dir(),
    ));

    $app->addRoutes([InvalidAuthController::class]);
})->throws(LogicException::class, 'must implement');
