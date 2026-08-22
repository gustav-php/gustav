<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use Nyholm\Psr7\ServerRequest;

it('discovers application-wide middleware without imperative bootstrap calls', function () {
    $app = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\Fixtures',
        cache: sys_get_temp_dir(),
    ));

    $response = $app->handle(new ServerRequest('GET', '/missing'));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getHeaderLine('X-Discovered-Middleware'))->toBe('provider');
});
