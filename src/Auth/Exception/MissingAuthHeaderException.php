<?php

namespace GustavPHP\Gustav\Auth\Exception;

class MissingAuthHeaderException extends AuthException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        string $message = 'Authorization header is missing',
        array $headers = [],
    ) {
        parent::__construct($message, $headers);
    }
}
