<?php

use GustavPHP\Gustav\{Application, Configuration, Discovery, Mode};
use GustavPHP\Tests\ExceptionHandlerFixtures\Additional\Handlers\AdditionalHandler;
use GustavPHP\Tests\ExceptionHandlerFixtures\ConventionalApplication\ExceptionHandlers\Nested\ConventionalHandler;
use GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\ExceptionHandlers\{FirstHandler, SecondHandler};
use GustavPHP\Tests\ExceptionHandlerFixtures\DuplicateApplication\Exceptions\DuplicateFailure;

it('discovers conventional and additional exception handlers recursively without duplicates', function () {
    Application::$configuration = new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ExceptionHandlerFixtures\\ConventionalApplication',
        exceptionHandlerNamespaces: [
            'GustavPHP\\Tests\\ExceptionHandlerFixtures\\ConventionalApplication\\ExceptionHandlers',
            'GustavPHP\\Tests\\ExceptionHandlerFixtures\\Additional\\Handlers',
        ],
    );

    $handlers = array_map(
        fn ($definition): string => $definition->handler,
        Discovery::discoverExceptionHandlers(),
    );
    sort($handlers);

    expect($handlers)->toBe([
        AdditionalHandler::class,
        ConventionalHandler::class,
    ]);
});

it('compiles discovered exception handlers while the application starts', function () {
    expect(new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ExceptionHandlerFixtures\\ConventionalApplication',
    )))->toBeInstanceOf(Application::class);
});

it('rejects duplicate discovered exception targets deterministically', function () {
    try {
        new Application(new Configuration(
            mode: Mode::Production,
            namespace: 'GustavPHP\\Tests\\ExceptionHandlerFixtures\\DuplicateApplication',
        ));
    } catch (LogicException $exception) {
        expect($exception->getMessage())
            ->toBe(
                "Exception type '" . DuplicateFailure::class . "' is handled by both "
                . FirstHandler::class . ' and ' . SecondHandler::class,
            );

        return;
    }

    throw new RuntimeException('Expected duplicate handlers to fail application startup');
});

it('rejects an invalid discovered exception handler while the application starts', function () {
    new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ExceptionHandlerFixtures\\InvalidApplication',
    ));
})->throws(LogicException::class, 'must declare one exception class');
