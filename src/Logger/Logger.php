<?php

namespace GustavPHP\Gustav\Logger;

use Stringable;

class Logger
{
    /**
     * Log a message.
     *
     * @param string|Stringable $text
     * @return void
     */
    public static function log(string|Stringable $text): void
    {
        echo $text;
    }
}
