<?php

namespace GustavPHP\Gustav\Logger;

use Stringable;
use Symfony\Component\Console\Output\ConsoleOutput;

class Logger
{
    /**
     * Symfony ConsoleOutput instance.
     *
     * @var null|ConsoleOutput
     */
    private static ?ConsoleOutput $logger = null;

    /**
     * Log a message.
     *
     * @param string $text
     * @return void
     */
    public static function log(string|Stringable $text): void
    {
        self::output()->writeln($text);
    }

    /**
     * Log a message with a newline. Initialises the logger if necessary.
     *
     * @return ConsoleOutput
     */
    private static function output(): ConsoleOutput
    {
        if (!self::$logger) {
            self::$logger = new ConsoleOutput();
        }
        return self::$logger;
    }
}
