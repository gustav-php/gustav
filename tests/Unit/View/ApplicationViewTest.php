<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use Nyholm\Psr7\ServerRequest;

it('uses an automatically discovered application view renderer', function () {
    $app = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ViewRendererFixtures\\ValidApplication',
        views: '/directory/that/does/not/exist/',
    ));

    $response = $app->handle(new ServerRequest('GET', '/'));

    expect($response->getStatusCode())->toBe(202)
        ->and($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=utf-8')
        ->and($response->getHeaderLine('X-Renderer'))->toBe('custom')
        ->and((string) $response->getBody())->toBe('custom:custom-home:Gustav');
});
