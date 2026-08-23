<?php

namespace GustavPHP\Gustav\Logger;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Psr\Log\{AbstractLogger, LogLevel};
use Stringable;
use Throwable;

final class JsonLogger extends AbstractLogger
{
    /** @var array<int, string> */
    private const LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    /** @var resource|null */
    private mixed $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct(mixed $stream = null)
    {
        if ($stream === null) {
            $opened = @fopen('php://stderr', 'wb');
            $this->stream = is_resource($opened) ? $opened : null;

            return;
        }

        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Logger stream must be a resource or null');
        }

        $this->stream = $stream;
    }

    /**
     * @param mixed $level
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level) || !in_array($level, self::LEVELS, true)) {
            throw new InvalidLogLevelException('Unknown PSR-3 log level');
        }

        $record = [
            'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->format('Y-m-d\TH:i:s.u\Z'),
            'level' => $level,
            'message' => $this->normalizeMessage($message),
            'context' => $this->normalizeContext($context),
        ];
        $encoded = json_encode(
            $record,
            JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_PARTIAL_OUTPUT_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
        );

        if (!is_string($encoded)) {
            $encoded = '{"level":"error","message":"Unable to encode log record","context":{}}';
        }

        $this->write($encoded . "\n");
    }

    /**
     * @param array<mixed> $context
     * @return array<mixed>
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeMessage(string|Stringable $message): string
    {
        if (is_string($message)) {
            return $this->normalizeString($message);
        }

        try {
            return $this->normalizeString((string) $message);
        } catch (Throwable) {
            return sprintf('[unprintable %s]', $message::class);
        }
    }

    private function normalizeString(string $value): string
    {
        $encoded = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);

        if (!is_string($encoded)) {
            return '[invalid string]';
        }

        $decoded = json_decode($encoded, true);

        return is_string($decoded) ? $decoded : '[invalid string]';
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function normalizeStructuredValue(array $value, int $depth): array
    {
        if ($depth >= 8) {
            return ['type' => 'array', 'truncated' => true];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeValue($item, $depth + 1);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $this->normalizeString($value->getMessage()),
                'code' => $value->getCode(),
                'file' => $this->normalizeString($value->getFile()),
                'line' => $value->getLine(),
                'trace' => $this->normalizeString($value->getTraceAsString()),
            ];
        }

        if (is_string($value)) {
            return $this->normalizeString($value);
        }

        if ($value === null || is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? $value : (string) $value;
        }

        if (is_resource($value)) {
            return ['resource' => get_resource_type($value)];
        }

        if ($value instanceof Stringable) {
            try {
                return $this->normalizeString((string) $value);
            } catch (Throwable $throwable) {
                return [
                    'class' => $value::class,
                    'stringify_error' => $throwable::class,
                ];
            }
        }

        if (is_array($value)) {
            return $this->normalizeStructuredValue($value, $depth);
        }

        if ($value instanceof JsonSerializable) {
            if ($depth >= 8) {
                return ['class' => $value::class, 'truncated' => true];
            }

            try {
                /** @throws Throwable */
                $serialized = $value->jsonSerialize();
            } catch (Throwable $throwable) {
                return [
                    'class' => $value::class,
                    'serialization_error' => $throwable::class,
                ];
            }

            return $this->normalizeValue($serialized, $depth + 1);
        }

        if (is_object($value)) {
            return ['class' => $value::class];
        }

        return ['type' => get_debug_type($value)];
    }

    private function write(string $record): void
    {
        $stream = $this->stream;
        if (!is_resource($stream)) {
            return;
        }

        try {
            @fwrite($stream, $record);
        } catch (Throwable) {
            // Logging must never change the application response or stop the worker.
        }
    }
}
