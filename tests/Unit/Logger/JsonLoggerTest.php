<?php

use GustavPHP\Gustav\Logger\{InvalidLogLevelException, JsonLogger};
use Psr\Log\LogLevel;

function readJsonLogRecord(mixed $stream): array
{
    rewind($stream);

    return json_decode((string) stream_get_contents($stream), true, 512, JSON_THROW_ON_ERROR);
}

it('writes structured PSR-3 records to one JSON line', function () {
    $stream = fopen('php://memory', 'w+');
    $logger = new JsonLogger($stream);
    $exception = new RuntimeException('Database unavailable');

    $logger->error('Request failed', [
        'request_id' => 'request-123',
        'exception' => $exception,
    ]);

    rewind($stream);
    $contents = (string) stream_get_contents($stream);
    $record = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    expect($record['timestamp'])->toBeString()
        ->and($record['level'])->toBe(LogLevel::ERROR)
        ->and($record['message'])->toBe('Request failed')
        ->and($record['context']['request_id'])->toBe('request-123')
        ->and($record['context']['exception']['class'])->toBe(RuntimeException::class)
        ->and($record['context']['exception']['message'])->toBe('Database unavailable')
        ->and(substr_count($contents, "\n"))->toBe(1);

    fclose($stream);
});

it('accepts arbitrary context without throwing or emitting invalid JSON', function () {
    $stream = fopen('php://memory', 'w+');
    $resource = fopen('php://memory', 'r');
    $recursive = [];
    $recursive['self'] = &$recursive;
    $logger = new JsonLogger($stream);

    $logger->info('Arbitrary context', [
        'invalid_utf8' => "\xB1\x31",
        'resource' => $resource,
        'recursive' => $recursive,
        'throwing_json' => new class () implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                throw new RuntimeException('Cannot serialize');
            }
        },
        'throwing_string' => new class () implements Stringable {
            public function __toString(): string
            {
                throw new RuntimeException('Cannot stringify');
            }
        },
    ]);

    $record = readJsonLogRecord($stream);

    expect($record['level'])->toBe(LogLevel::INFO)
        ->and($record['context'])->toBeArray();

    fclose($resource);
    fclose($stream);
});

it('rejects unknown log levels', function () {
    $stream = fopen('php://memory', 'w+');
    $logger = new JsonLogger($stream);

    try {
        $logger->log('verbose', 'Unsupported');
    } finally {
        fclose($stream);
    }
})->throws(InvalidLogLevelException::class);

it('does not throw when its output stream becomes unavailable', function () {
    $stream = fopen('php://memory', 'w+');
    $logger = new JsonLogger($stream);
    fclose($stream);

    $logger->error('Cannot be written');

    expect(true)->toBeTrue();
});

it('rejects invalid output streams', function () {
    new JsonLogger(false);
})->throws(InvalidArgumentException::class);
