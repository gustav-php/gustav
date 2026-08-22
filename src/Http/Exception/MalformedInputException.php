<?php

namespace GustavPHP\Gustav\Http\Exception;

final class MalformedInputException extends RequestInputException
{
    public function __construct(string $message = 'Malformed request input')
    {
        parent::__construct(400, $message);
    }
}
