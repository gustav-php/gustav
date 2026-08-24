<?php

namespace GustavPHP\Gustav\Http\Exception;

final class CsrfException extends RequestInputException
{
    public function __construct()
    {
        parent::__construct(403, 'CSRF token is invalid');
    }
}
