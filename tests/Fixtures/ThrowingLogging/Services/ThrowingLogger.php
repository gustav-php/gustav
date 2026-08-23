<?php

namespace GustavPHP\Tests\Fixtures\ThrowingLogging\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;
use Psr\Log\{AbstractLogger, LoggerInterface};
use RuntimeException;
use Stringable;

#[Service(as: LoggerInterface::class, lifetime: Lifetime::Singleton)]
final class ThrowingLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        throw new RuntimeException('Logger unavailable');
    }
}
