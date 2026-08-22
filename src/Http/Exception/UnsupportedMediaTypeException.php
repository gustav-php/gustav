<?php

namespace GustavPHP\Gustav\Http\Exception;

final class UnsupportedMediaTypeException extends RequestInputException
{
    public function __construct(string $message = 'Unsupported media type')
    {
        parent::__construct(415, $message);
    }
}
