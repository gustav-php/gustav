<?php

namespace GustavPHP\Gustav\Auth\Exception;

use GustavPHP\Gustav\Http\Exception\HttpException;

class ForbiddenException extends HttpException
{
    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(string $message = 'Forbidden', array $headers = [])
    {
        parent::__construct(403, $message, $headers);
    }
}
