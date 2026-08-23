<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Gustav\Event\{Dispatcher, ListenerDefinition};
use GustavPHP\Tests\EventFixtures\Invalid\Events\{
    MissingInvokeListener,
    MultipleParametersListener,
    NullableListener,
    ReturningListener,
    ScalarListener,
    UnionListener
};
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\{
    RecordedEvent,
    StoppableEvent,
    UnhandledEvent
};
use Nyholm\Psr7\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

function eventTestApplication(): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\EventFixtures\\ValidApplication',
        cache: sys_get_temp_dir() . '/gustav-event-tests/',
        serviceNamespaces: ['GustavPHP\\Tests\\Fixtures\\QuietLogging\\Services'],
    ));
}

it('describes listener priority as attribute metadata', function () {
    expect((new Listener(priority: 25))->priority)->toBe(25);
});

it('discovers typed listeners and resolves one injected instance per scope', function () {
    $services = eventTestApplication()->services();
    $services->build();
    $firstScope = $services->createScope();
    $secondScope = $services->createScope();

    try {
        $firstDispatcher = $firstScope->get(EventDispatcherInterface::class);
        $secondDispatcher = $secondScope->get(EventDispatcherInterface::class);
        $first = new RecordedEvent();
        $again = new RecordedEvent();
        $otherScope = new RecordedEvent();

        expect($firstDispatcher)->toBeInstanceOf(Dispatcher::class)
            ->and($firstDispatcher->dispatch($first))->toBe($first);
        $firstDispatcher->dispatch($again);
        $secondDispatcher->dispatch($otherScope);

        expect(array_column($first->records, 'listener'))->toBe(['high', 'low'])
            ->and(array_column($again->records, 'listenerId'))
            ->toBe(array_column($first->records, 'listenerId'))
            ->and(array_unique(array_column($first->records, 'dependencyId')))->toHaveCount(1)
            ->and(array_column($otherScope->records, 'listenerId'))
            ->not->toBe(array_column($first->records, 'listenerId'))
            ->and(array_column($otherScope->records, 'dependencyId'))
            ->not->toBe(array_column($first->records, 'dependencyId'));
    } finally {
        $firstScope->release();
        $secondScope->release();
    }
});

it('honors stoppable events and treats events without listeners as a no-op', function () {
    $services = eventTestApplication()->services();
    $services->build();
    $scope = $services->createScope();

    try {
        $dispatcher = $scope->get(EventDispatcherInterface::class);
        $stoppable = new StoppableEvent();
        $alreadyStopped = new StoppableEvent();
        $alreadyStopped->stop();
        $unhandled = new UnhandledEvent();

        expect($dispatcher->dispatch($stoppable))->toBe($stoppable)
            ->and($stoppable->listeners)->toBe(['stop'])
            ->and($stoppable->isPropagationStopped())->toBeTrue()
            ->and($dispatcher->dispatch($alreadyStopped))->toBe($alreadyStopped)
            ->and($alreadyStopped->listeners)->toBe([])
            ->and($dispatcher->dispatch($unhandled))->toBe($unhandled);
    } finally {
        $scope->release();
    }
});

it('dispatches events inside command scopes', function () {
    $tester = new ApplicationTester(eventTestApplication()->console());

    expect($tester->run(['command' => 'events:dispatch']))->toBe(Command::SUCCESS)
        ->and($tester->getDisplay(true))->toContain('high,low');
});

it('keeps listener failures production safe and serves the next request', function () {
    $application = eventTestApplication();

    $first = $application->handle(new ServerRequest('GET', '/events'));
    $second = $application->handle(new ServerRequest('GET', '/events'));
    $failure = $application->handle(new ServerRequest('GET', '/events/fail'));
    $next = $application->handle(new ServerRequest('GET', '/events'));
    $firstBody = json_decode((string) $first->getBody(), true, flags: JSON_THROW_ON_ERROR);
    $secondBody = json_decode((string) $second->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($first->getStatusCode())->toBe(200)
        ->and($second->getStatusCode())->toBe(200)
        ->and(array_column($secondBody['records'], 'listenerId'))
        ->not->toBe(array_column($firstBody['records'], 'listenerId'))
        ->and($failure->getStatusCode())->toBe(500)
        ->and((string) $failure->getBody())->not->toContain('secret listener failure')
        ->and($next->getStatusCode())->toBe(200);
});

it('rejects invalid listener signatures during startup compilation', function (string $listener, string $message) {
    expect(fn () => ListenerDefinition::compile($listener))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing invoke method' => [MissingInvokeListener::class, 'must declare a public __invoke() method'],
    'multiple parameters' => [MultipleParametersListener::class, 'must accept exactly one event parameter'],
    'scalar event' => [ScalarListener::class, 'must declare one event class or interface'],
    'union event' => [UnionListener::class, 'must declare one event class or interface'],
    'nullable event' => [NullableListener::class, 'event parameter cannot be nullable'],
    'non-void return' => [ReturningListener::class, 'must declare void'],
]);

it('fails application startup when a discovered listener is invalid', function () {
    new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\EventFixtures\\Invalid',
        cache: sys_get_temp_dir() . '/gustav-invalid-event-tests/',
    ));
})->throws(InvalidArgumentException::class, 'Event listener');
