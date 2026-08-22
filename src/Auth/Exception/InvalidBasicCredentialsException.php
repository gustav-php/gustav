<?php

namespace GustavPHP\Gustav\Auth\Exception;

class InvalidBasicCredentialsException extends AuthException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        string $message = 'Invalid basic authentication credentials',
        array $headers = [],
    ) {
        parent::__construct($message, $headers);
    }
}
