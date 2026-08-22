<?php

namespace GustavPHP\Gustav\Auth\Exception;

class InvalidAuthHeaderException extends AuthException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        string $message = 'Invalid authorization header format',
        array $headers = [],
    ) {
        parent::__construct($message, $headers);
    }
}
