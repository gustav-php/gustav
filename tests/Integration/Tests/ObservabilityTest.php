<?php

use GustavPHP\Gustav\{Application, Configuration, Mode};
use GustavPHP\Gustav\Session\SessionOptions;
use GustavPHP\Tests\Fixtures\Observability\Services\RecordingLogger;

use function GustavPHP\Tests\Integration\integrationSessionDirectory;

use Nyholm\Psr7\ServerRequest;
use Psr\Log\LogLevel;

function observabilityApplication(
    string $namespace = 'GustavPHP\\Tests\\Fixtures\\Observability',
    Mode $mode = Mode::Production,
): Application {
    return new Application(new Configuration(
        mode: $mode,
        namespace: $namespace,
        routeNamespaces: ['GustavPHP\\Tests\\Integration\\Routes'],
        session: new SessionOptions(directory: integrationSessionDirectory()),
    ));
}

it('injects and propagates a generated request id', function () {
    $response = observabilityApplication()->handle(new ServerRequest('GET', '/kernel/request-id'));
    $body = json_decode((string) $response->getBody(), true);
    $requestId = $response->getHeaderLine('X-Request-ID');

    expect($response->getStatusCode())->toBe(200)
        ->and($requestId)->toMatch('/^[a-f0-9]{32}$/')
        ->and($body['requestId'])->toBe($requestId)
        ->and($body['attribute'])->toBe($requestId);
});

it('reports server failures in development mode too', function () {
    RecordingLogger::reset();
    $response = observabilityApplication(mode: Mode::Development)
        ->handle(new ServerRequest('GET', '/kernel/server-error'));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('X-Request-ID'))->not->toBe('')
        ->and($response->getHeaderLine('Content-Type'))->toBe('text/html')
        ->and((string) $response->getBody())->toContain('internal secret &lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>alert(1)</script>')
        ->and(RecordingLogger::$records)->toHaveCount(1);
});

it('preserves a safe request id and replaces an unsafe one', function () {
    $app = observabilityApplication();
    $preserved = $app->handle(new ServerRequest(
        'GET',
        '/kernel/request-id',
        ['X-Request-ID' => 'gateway.request-123'],
    ));
    $replaced = $app->handle(new ServerRequest(
        'GET',
        '/kernel/request-id',
        ['X-Request-ID' => 'unsafe request id'],
    ));

    expect($preserved->getHeaderLine('X-Request-ID'))->toBe('gateway.request-123')
        ->and($replaced->getHeaderLine('X-Request-ID'))->toMatch('/^[a-f0-9]{32}$/')
        ->not->toBe('unsafe request id');
});

it('keeps the canonical request id when a response supplies another value', function () {
    $response = observabilityApplication()->handle(new ServerRequest(
        'GET',
        '/kernel/request-id-override',
        ['X-Request-ID' => 'gateway.request-123'],
    ));

    expect($response->getHeaderLine('X-Request-ID'))->toBe('gateway.request-123');
});

it('reports an unexpected server failure exactly once without exposing it', function () {
    RecordingLogger::reset();
    $response = observabilityApplication()->handle(new ServerRequest(
        'GET',
        '/kernel/server-error',
        ['X-Request-ID' => 'request-500'],
    ));
    $body = json_decode((string) $response->getBody(), true);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('X-Request-ID'))->toBe('request-500')
        ->and($body['error']['message'])->toBe('Server Error')
        ->and((string) $response->getBody())->not->toContain('internal secret')
        ->and(RecordingLogger::$records)->toHaveCount(1)
        ->and(RecordingLogger::$records[0]['level'])->toBe(LogLevel::ERROR)
        ->and(RecordingLogger::$records[0]['context']['request_id'])->toBe('request-500')
        ->and(RecordingLogger::$records[0]['context']['http.method'])->toBe('GET')
        ->and(RecordingLogger::$records[0]['context']['http.path'])->toBe('/kernel/server-error')
        ->and(RecordingLogger::$records[0]['context']['http.status_code'])->toBe(500)
        ->and(RecordingLogger::$records[0]['context']['exception'])->toBeInstanceOf(RuntimeException::class);
});

it('reports explicit server HTTP exceptions but keeps client errors quiet', function () {
    RecordingLogger::reset();
    $app = observabilityApplication();

    $clientError = $app->handle(new ServerRequest('GET', '/kernel/error'));
    $missing = $app->handle(new ServerRequest('GET', '/missing'));

    expect($clientError->getStatusCode())->toBe(418)
        ->and($clientError->getHeaderLine('X-Request-ID'))->not->toBe('')
        ->and($missing->getStatusCode())->toBe(404)
        ->and($missing->getHeaderLine('X-Request-ID'))->not->toBe('')
        ->and(RecordingLogger::$records)->toBe([]);

    $serverError = $app->handle(new ServerRequest('GET', '/kernel/unavailable'));

    expect($serverError->getStatusCode())->toBe(503)
        ->and(RecordingLogger::$records)->toHaveCount(1)
        ->and(RecordingLogger::$records[0]['context']['http.status_code'])->toBe(503);
});

it('keeps serving when the configured logger fails', function () {
    $app = observabilityApplication('GustavPHP\\Tests\\Fixtures\\ThrowingLogging');

    $failed = $app->handle(new ServerRequest('GET', '/kernel/server-error'));
    $next = $app->handle(new ServerRequest('GET', '/responses/plaintext'));

    expect($failed->getStatusCode())->toBe(500)
        ->and($failed->getHeaderLine('X-Request-ID'))->not->toBe('')
        ->and($next->getStatusCode())->toBe(200)
        ->and((string) $next->getBody())->toBe('lorem ipsum');
});
