<?php

namespace GustavPHP\Tests\Fixtures\ThrowingLogging\Services;

use GustavPHP\Gustav\Logger\{ExceptionReporter, JsonLogger};
use GustavPHP\Gustav\Service\{Container, Provider};
use Psr\Log\LoggerInterface;
use RuntimeException;

final class InMemoryFallbackProvider implements Provider
{
    public function register(Container $services): void
    {
        $stream = fopen('php://memory', 'w+');
        if ($stream === false) {
            throw new RuntimeException('Unable to create the test log stream');
        }

        $fallback = new JsonLogger($stream);
        $services->scoped(
            ExceptionReporter::class,
            function (Container $scope) use ($fallback): ExceptionReporter {
                $logger = $scope->get(LoggerInterface::class);
                if (!$logger instanceof LoggerInterface) {
                    throw new RuntimeException('Logger service is invalid');
                }

                return new ExceptionReporter($logger, $fallback);
            },
        );
    }
}
