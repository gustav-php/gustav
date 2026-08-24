<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers\ScopedFailureHandler;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\{ServerFailure, UnmatchedFailure};
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Services\ScopeProbe;
use GustavPHP\Tests\Fixtures\Observability\Services\RecordingLogger;
use Nyholm\Psr7\ServerRequest;
use Psr\Log\LogLevel;

function applicationWithExceptionHandlers(bool $fallback = false): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\ExceptionHandlerFixtures\\ValidApplication',
        views: __DIR__ . '/../../ExceptionHandlerFixtures/ValidApplication/views',
        serviceNamespaces: ['GustavPHP\\Tests\\Fixtures\\Observability\\Services'],
        exceptionHandlerNamespaces: $fallback
            ? ['GustavPHP\\Tests\\ExceptionHandlerFixtures\\Fallback\\ExceptionHandlers']
            : [],
    ));
}

beforeEach(function () {
    RecordingLogger::reset();
    ScopeProbe::reset();
    ScopedFailureHandler::reset();
});

it('maps exact exceptions before their nearest registered parent', function () {
    $application = applicationWithExceptionHandlers();

    $domain = $application->handle(new ServerRequest('GET', '/handlers/domain'));
    $specific = $application->handle(new ServerRequest('GET', '/handlers/specific'));
    $child = $application->handle(new ServerRequest('GET', '/handlers/child'));

    expect($domain->getStatusCode())->toBe(404)
        ->and($domain->getHeaderLine('X-Exception-Handler'))->toBe('domain')
        ->and($domain->getHeaderLine('X-Inspected-Status'))->toBe('404')
        ->and((string) $domain->getBody())->toBe('domain failure')
        ->and($specific->getStatusCode())->toBe(409)
        ->and($specific->getHeaderLine('X-Exception-Handler'))->toBe('specific')
        ->and($specific->getHeaderLine('X-Inspected-Status'))->toBe('409')
        ->and((string) $specific->getBody())->toBe('specific failure')
        ->and($child->getStatusCode())->toBe(409)
        ->and($child->getHeaderLine('X-Exception-Handler'))->toBe('specific')
        ->and((string) $child->getBody())->toBe('child failure');
});

it('supports explicit PSR responses and rendered views', function () {
    $application = applicationWithExceptionHandlers();

    $psr = $application->handle(new ServerRequest('GET', '/handlers/psr'));
    $view = $application->handle(new ServerRequest('GET', '/handlers/view'));

    expect($psr->getStatusCode())->toBe(202)
        ->and($psr->getHeaderLine('X-Exception-Handler'))->toBe('psr')
        ->and((string) $psr->getBody())->toBe('accepted failure')
        ->and($view->getStatusCode())->toBe(410)
        ->and($view->getHeaderLine('X-Exception-Handler'))->toBe('view')
        ->and($view->getHeaderLine('Content-Type'))->toBe('text/html; charset=utf-8')
        ->and(trim((string) $view->getBody()))
        ->toBe('<h1>gone &amp; &lt;strong&gt;hidden&lt;/strong&gt;</h1>');
});

it('lazily injects the active request into a scoped handler', function () {
    $application = applicationWithExceptionHandlers();

    expect(ScopedFailureHandler::instances())->toBe(0);

    $first = $application->handle(new ServerRequest(
        'GET',
        '/handlers/scoped',
        ['X-Request-ID' => 'handler-request-one'],
    ));
    $firstBody = json_decode((string) $first->getBody(), true, flags: JSON_THROW_ON_ERROR);
    $firstReleased = !ScopeProbe::alive();
    $second = $application->handle(new ServerRequest(
        'GET',
        '/handlers/scoped',
        ['X-Request-ID' => 'handler-request-two'],
    ));
    $secondBody = json_decode((string) $second->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($first->getStatusCode())->toBe(418)
        ->and($firstBody)->toBe([
            'message' => 'scoped failure',
            'handler' => 1,
            'scope' => 1,
            'requestId' => 'handler-request-one',
            'path' => '/handlers/scoped',
        ])
        ->and($firstReleased)->toBeTrue()
        ->and($second->getStatusCode())->toBe(418)
        ->and($secondBody)->toBe([
            'message' => 'scoped failure',
            'handler' => 2,
            'scope' => 2,
            'requestId' => 'handler-request-two',
            'path' => '/handlers/scoped',
        ])
        ->and(ScopeProbe::alive())->toBeFalse();
});

it('maps failures thrown by application middleware at the outer request boundary', function () {
    $response = applicationWithExceptionHandlers()
        ->handle(new ServerRequest('GET', '/handlers/outer'));

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getHeaderLine('Retry-After'))->toBe('5')
        ->and((string) $response->getBody())->toBe('outer middleware mapped');
});

it('uses a configured Throwable fallback without replacing built-in HTTP and input errors', function () {
    $application = applicationWithExceptionHandlers(fallback: true);

    $fallback = $application->handle(new ServerRequest('GET', '/handlers/unmatched'));
    $http = $application->handle(new ServerRequest('GET', '/handlers/http'));
    $input = $application->handle(new ServerRequest('GET', '/handlers/input'));
    $inputBody = json_decode((string) $input->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($fallback->getStatusCode())->toBe(499)
        ->and($fallback->getHeaderLine('X-Exception-Handler'))->toBe('fallback')
        ->and((string) $fallback->getBody())->toContain(UnmatchedFailure::class)
        ->and($http->getStatusCode())->toBe(418)
        ->and($http->getHeaderLine('X-Built-In'))->toBe('yes')
        ->and($http->getHeaderLine('X-Exception-Handler'))->toBe('')
        ->and($input->getStatusCode())->toBe(422)
        ->and($input->getHeaderLine('X-Exception-Handler'))->toBe('')
        ->and($inputBody['error']['message'])->toBe('Validation failed')
        ->and($inputBody['error']['violations'])->toHaveCount(1);
});

it('keeps mapped client failures quiet and reports mapped server failures once', function () {
    $application = applicationWithExceptionHandlers();

    $client = $application->handle(new ServerRequest('GET', '/handlers/client'));

    expect($client->getStatusCode())->toBe(422)
        ->and(RecordingLogger::$records)->toBe([]);

    $server = $application->handle(new ServerRequest('GET', '/handlers/server'));
    $serverBody = json_decode((string) $server->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($server->getStatusCode())->toBe(503)
        ->and($serverBody['error']['message'])->toBe('Temporarily unavailable')
        ->and((string) $server->getBody())->not->toContain('server source secret')
        ->and(RecordingLogger::$records)->toHaveCount(1)
        ->and(RecordingLogger::$records[0]['level'])->toBe(LogLevel::ERROR)
        ->and(RecordingLogger::$records[0]['context']['http.status_code'])->toBe(503)
        ->and(RecordingLogger::$records[0]['context']['exception'])->toBeInstanceOf(ServerFailure::class);
});

it('does not recursively handle handler failures and keeps serving after releasing the scope', function () {
    $application = applicationWithExceptionHandlers(fallback: true);

    $failed = $application->handle(new ServerRequest('GET', '/handlers/handler-failure'));
    gc_collect_cycles();
    $released = !ScopeProbe::alive();
    $next = $application->handle(new ServerRequest('GET', '/handlers/ok'));

    expect($failed->getStatusCode())->toBe(500)
        ->and($failed->getHeaderLine('X-Exception-Handler'))->toBe('')
        ->and((string) $failed->getBody())->not->toContain('handler secret')
        ->and((string) $failed->getBody())->not->toContain('domain secret')
        ->and($released)->toBeTrue()
        ->and(ScopeProbe::instances())->toBe(1)
        ->and(RecordingLogger::$records)->toHaveCount(1)
        ->and(RecordingLogger::$records[0]['context']['http.status_code'])->toBe(500)
        ->and(RecordingLogger::$records[0]['context']['exception']::class)->toBe(RuntimeException::class)
        ->and($next->getStatusCode())->toBe(200)
        ->and(json_decode((string) $next->getBody(), true, flags: JSON_THROW_ON_ERROR))->toBe(['ok' => true]);
});

it('reports a later handler failure after an earlier mapped server failure', function () {
    $response = applicationWithExceptionHandlers()
        ->handle(new ServerRequest('GET', '/handlers/chained-handler-failure'));

    expect($response->getStatusCode())->toBe(500)
        ->and((string) $response->getBody())->not->toContain('handler secret')
        ->and((string) $response->getBody())->not->toContain('outer domain secret')
        ->and((string) $response->getBody())->not->toContain('first server secret')
        ->and(RecordingLogger::$records)->toHaveCount(2)
        ->and(RecordingLogger::$records[0]['context']['http.status_code'])->toBe(503)
        ->and(RecordingLogger::$records[0]['context']['exception'])->toBeInstanceOf(ServerFailure::class)
        ->and(RecordingLogger::$records[1]['context']['http.status_code'])->toBe(500)
        ->and(RecordingLogger::$records[1]['context']['exception']::class)->toBe(RuntimeException::class)
        ->and(ScopeProbe::alive())->toBeFalse();
});

it('preserves the production-safe response for unmatched unexpected failures', function () {
    $response = applicationWithExceptionHandlers()
        ->handle(new ServerRequest('GET', '/handlers/unmatched'));
    $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('X-Inspected-Status'))->toBe('500')
        ->and($body['error']['message'])->toBe('Server Error')
        ->and((string) $response->getBody())->not->toContain('unmatched secret')
        ->and(RecordingLogger::$records)->toHaveCount(1)
        ->and(RecordingLogger::$records[0]['context']['exception'])->toBeInstanceOf(UnmatchedFailure::class);
});
