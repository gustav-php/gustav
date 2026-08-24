<?php

use GustavPHP\Gustav\Http\{ExceptionHandlerDefinition, ExceptionHandlerRegistry, RequestId};
use GustavPHP\Gustav\Service\Container;
use GustavPHP\Gustav\View\{PhpViewRenderer, ViewRendererInterface};
use GustavPHP\Tests\ExceptionHandlerFixtures\Registry\{
    ChildFailure,
    DuplicateParentHandler,
    InjectedFailure,
    InjectedHandler,
    OtherFailure,
    ParentFailure,
    ParentHandler,
    PsrFailure,
    PsrHandler,
    SpecificFailure,
    SpecificHandler,
    ThrowableHandler,
    ViewFailure,
    ViewHandler
};
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @param class-string ...$handlers
 * @return list<ExceptionHandlerDefinition>
 */
function compiledExceptionHandlers(string ...$handlers): array
{
    return array_map(
        fn (string $handler): ExceptionHandlerDefinition => ExceptionHandlerDefinition::compile($handler),
        $handlers,
    );
}

function exceptionHandlerScope(string $path = '/registry', string $requestId = 'registry-request'): Container
{
    $services = new Container();
    $services->singleton(
        ViewRendererInterface::class,
        new PhpViewRenderer(__DIR__ . '/../../ExceptionHandlerFixtures/Registry/views'),
    );
    $services->build();
    $request = new ServerRequest('GET', $path);

    return $services->createScope([
        RequestId::class => RequestId::fromString($requestId),
        ServerRequestInterface::class => $request,
    ]);
}

it('selects the exact exception, nearest parent, and Throwable fallback in order', function () {
    $registry = new ExceptionHandlerRegistry(compiledExceptionHandlers(
        ThrowableHandler::class,
        ParentHandler::class,
        SpecificHandler::class,
    ));
    $scope = exceptionHandlerScope();

    try {
        $exact = $registry->handle(new SpecificFailure('exact'), $scope);
        $nearest = $registry->handle(new ChildFailure('nearest'), $scope);
        $parent = $registry->handle(new ParentFailure('parent'), $scope);
        $fallback = $registry->handle(new OtherFailure('fallback'), $scope);

        expect($exact?->getStatusCode())->toBe(409)
            ->and($exact?->getHeaderLine('X-Handler'))->toBe('specific')
            ->and((string) $exact?->getBody())->toBe('exact')
            ->and($nearest?->getStatusCode())->toBe(409)
            ->and($nearest?->getHeaderLine('X-Handler'))->toBe('specific')
            ->and($parent?->getStatusCode())->toBe(404)
            ->and($parent?->getHeaderLine('X-Handler'))->toBe('parent')
            ->and($fallback?->getStatusCode())->toBe(500)
            ->and($fallback?->getHeaderLine('X-Handler'))->toBe('throwable');
    } finally {
        $scope->release();
    }
});

it('returns null when no handler matches', function () {
    $registry = new ExceptionHandlerRegistry(compiledExceptionHandlers(ParentHandler::class));
    $scope = exceptionHandlerScope();

    try {
        expect($registry->handle(new OtherFailure(), $scope))->toBeNull();
    } finally {
        $scope->release();
    }
});

it('builds PSR responses and renders views through their compiled response handlers', function () {
    $registry = new ExceptionHandlerRegistry(compiledExceptionHandlers(
        PsrHandler::class,
        ViewHandler::class,
    ));
    $scope = exceptionHandlerScope();

    try {
        $psr = $registry->handle(new PsrFailure('accepted'), $scope);
        $view = $registry->handle(new ViewFailure('gone & hidden'), $scope);

        expect($psr?->getStatusCode())->toBe(202)
            ->and($psr?->getHeaderLine('X-Handler'))->toBe('psr')
            ->and((string) $psr?->getBody())->toBe('accepted')
            ->and($view?->getStatusCode())->toBe(410)
            ->and($view?->getHeaderLine('X-Handler'))->toBe('view')
            ->and($view?->getHeaderLine('Content-Type'))->toBe('text/html; charset=utf-8')
            ->and(trim((string) $view?->getBody()))->toBe('<h1>gone &amp; hidden</h1>');
    } finally {
        $scope->release();
    }
});

it('lazily resolves one injected handler instance per request scope', function () {
    InjectedHandler::reset();
    $registry = new ExceptionHandlerRegistry(compiledExceptionHandlers(InjectedHandler::class));

    expect(InjectedHandler::instances())->toBe(0);

    $firstScope = exceptionHandlerScope('/first', 'request-one');
    $secondScope = exceptionHandlerScope('/second', 'request-two');

    try {
        $first = $registry->handle(new InjectedFailure('first'), $firstScope);
        $again = $registry->handle(new InjectedFailure('again'), $firstScope);
        $second = $registry->handle(new InjectedFailure('second'), $secondScope);

        expect(InjectedHandler::instances())->toBe(2)
            ->and($first?->getHeaderLine('X-Handler-Instance'))->toBe('1')
            ->and($again?->getHeaderLine('X-Handler-Instance'))->toBe('1')
            ->and($second?->getHeaderLine('X-Handler-Instance'))->toBe('2')
            ->and($first?->getHeaderLine('X-Request-ID'))->toBe('request-one')
            ->and($second?->getHeaderLine('X-Request-ID'))->toBe('request-two')
            ->and($first?->getHeaderLine('X-Request-Path'))->toBe('/first')
            ->and($second?->getHeaderLine('X-Request-Path'))->toBe('/second');
    } finally {
        $firstScope->release();
        $secondScope->release();
    }
});

it('rejects duplicate exception targets deterministically', function () {
    $compile = function (array $handlers): string {
        try {
            new ExceptionHandlerRegistry(compiledExceptionHandlers(...$handlers));
        } catch (LogicException $exception) {
            return $exception->getMessage();
        }

        throw new RuntimeException('Expected duplicate handlers to fail');
    };

    $forward = $compile([ParentHandler::class, DuplicateParentHandler::class]);
    $reverse = $compile([DuplicateParentHandler::class, ParentHandler::class]);

    expect($forward)->toBe($reverse)
        ->and($forward)->toBe(
            "Exception type '" . ParentFailure::class . "' is handled by both "
            . DuplicateParentHandler::class . ' and ' . ParentHandler::class,
        );
});
