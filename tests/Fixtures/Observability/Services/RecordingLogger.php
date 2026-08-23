<?php

namespace GustavPHP\Tests\Fixtures\Observability\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;
use Psr\Log\{AbstractLogger, LoggerInterface};
use Stringable;

#[Service(as: LoggerInterface::class, lifetime: Lifetime::Singleton)]
final class RecordingLogger extends AbstractLogger
{
    /**
     * @var array<int, array{
     *     level: mixed,
     *     message: string,
     *     context: array<string, mixed>
     * }>
     */
    public static array $records = [];

    /**
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        self::$records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public static function reset(): void
    {
        self::$records = [];
    }
}
