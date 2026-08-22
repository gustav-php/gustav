<?php

namespace GustavPHP\Gustav\Http\Exception;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException('HTTP exception status must be between 400 and 599');
        }

        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * @return array<string, string|array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
