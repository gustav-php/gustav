<?php

namespace GustavPHP\Gustav\Logger;

use Symfony\Component\Console\Output\ConsoleOutput;

class Logger
{
    private static ?ConsoleOutput $logger = null;
    public static function log(string $text): void
    {
        self::output()->writeln("{$text}");
    }

    private static function output(): ConsoleOutput
    {
        if (!self::$logger) {
            self::$logger = new ConsoleOutput();
        }
        return self::$logger;
    }
}
