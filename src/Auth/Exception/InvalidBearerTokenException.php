<?php

namespace GustavPHP\Gustav\Auth\Exception;

class InvalidBearerTokenException extends AuthException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        string $message = 'Invalid bearer token format',
        array $headers = [],
    ) {
        parent::__construct($message, $headers);
    }
}
