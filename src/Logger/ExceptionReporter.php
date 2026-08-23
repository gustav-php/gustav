<?php

namespace GustavPHP\Gustav\Logger;

use GustavPHP\Gustav\Http\RequestId;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ExceptionReporter
{
    public function __construct(
        private LoggerInterface $logger,
        private JsonLogger $fallback,
    ) {
    }

    public function report(
        Throwable $exception,
        ServerRequestInterface $request,
        RequestId $requestId,
        int $status,
    ): void {
        if ($status < 500) {
            return;
        }

        $context = [
            'request_id' => (string) $requestId,
            'http.method' => $request->getMethod(),
            'http.path' => $request->getUri()->getPath(),
            'http.status_code' => $status,
            'exception' => $exception,
        ];

        try {
            $this->logger->error('Request failed', $context);
        } catch (Throwable $loggerFailure) {
            try {
                $this->fallback->error('Request failed', [
                    ...$context,
                    'logger_failure' => $loggerFailure,
                ]);
            } catch (Throwable) {
                // Reporting failures must not alter the response or stop the worker.
            }
        }
    }
}
