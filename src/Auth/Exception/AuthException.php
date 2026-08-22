<?php

namespace GustavPHP\Gustav\Auth\Exception;

use GustavPHP\Gustav\Http\Exception\HttpException;

abstract class AuthException extends HttpException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(string $message = 'Unauthorized', array $headers = [])
    {
        parent::__construct(401, $message, $headers);
    }
}
