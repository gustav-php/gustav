<?php

namespace GustavPHP\Gustav\Traits;

use GustavPHP\Gustav\Logger\Logger as InternalLogger;

trait Logger
{
    public function log(string|\Stringable $message): void
    {
        InternalLogger::log($message);
    }
}
