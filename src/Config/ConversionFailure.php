<?php

namespace GustavPHP\Gustav\Config;

use RuntimeException;

/** @internal */
final class ConversionFailure extends RuntimeException
{
    public function __construct(
        public readonly string $violationCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
