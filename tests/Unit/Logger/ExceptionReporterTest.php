<?php

use GustavPHP\Gustav\Http\RequestId;
use GustavPHP\Gustav\Logger\{ExceptionReporter, JsonLogger};
use Nyholm\Psr7\ServerRequest;
use Psr\Log\AbstractLogger;

function readReporterLogRecord(mixed $stream): array
{
    rewind($stream);

    return json_decode((string) stream_get_contents($stream), true, 512, JSON_THROW_ON_ERROR);
}

it('reports server failures with safe request context', function () {
    $logger = new class () extends AbstractLogger {
        /** @var array<int, array{level: mixed, message: string, context: array<string, mixed>}> */
        public array $records = [];

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->records[] = [
                'level' => $level,
                'message' => (string) $message,
                'context' => $context,
            ];
        }
    };
    $fallbackStream = fopen('php://memory', 'w+');
    $reporter = new ExceptionReporter($logger, new JsonLogger($fallbackStream));
    $exception = new RuntimeException('Internal failure');
    $request = new ServerRequest(
        'POST',
        '/dogs?token=query-secret',
        [
            'Authorization' => 'Bearer header-secret',
            'Cookie' => 'session=cookie-secret',
        ],
        'body-secret',
    );

    $reporter->report($exception, $request, RequestId::fromString('request-123'), 500);

    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['message'])->toBe('Request failed')
        ->and($logger->records[0]['context'])->toMatchArray([
            'request_id' => 'request-123',
            'http.method' => 'POST',
            'http.path' => '/dogs',
            'http.status_code' => 500,
            'exception' => $exception,
        ])
        ->and(json_encode($logger->records[0]['context']))
        ->not->toContain('query-secret')
        ->not->toContain('header-secret')
        ->not->toContain('cookie-secret')
        ->not->toContain('body-secret');

    fclose($fallbackStream);
});

it('does not report client errors', function () {
    $logger = new class () extends AbstractLogger {
        public int $calls = 0;

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->calls++;
        }
    };
    $fallbackStream = fopen('php://memory', 'w+');
    $reporter = new ExceptionReporter($logger, new JsonLogger($fallbackStream));

    $reporter->report(
        new RuntimeException('Invalid request'),
        new ServerRequest('GET', '/dogs'),
        RequestId::fromString('request-123'),
        422,
    );

    expect($logger->calls)->toBe(0);
    fclose($fallbackStream);
});

it('falls back safely when the application logger throws', function () {
    $logger = new class () extends AbstractLogger {
        public function log($level, string|\Stringable $message, array $context = []): void
        {
            throw new RuntimeException('Logger unavailable');
        }
    };
    $fallbackStream = fopen('php://memory', 'w+');
    $reporter = new ExceptionReporter($logger, new JsonLogger($fallbackStream));

    $reporter->report(
        new RuntimeException('Application failure'),
        new ServerRequest('GET', '/dogs'),
        RequestId::fromString('request-123'),
        500,
    );
    $record = readReporterLogRecord($fallbackStream);

    expect($record['message'])->toBe('Request failed')
        ->and($record['context']['request_id'])->toBe('request-123')
        ->and($record['context']['logger_failure']['class'])->toBe(RuntimeException::class);

    fclose($fallbackStream);
});
