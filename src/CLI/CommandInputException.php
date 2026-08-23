<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Validation\Violation;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

/** @internal */
final class CommandInputException extends RuntimeException
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(public readonly array $violations)
    {
        if ($violations === []) {
            throw new InvalidArgumentException('A command input exception requires at least one violation');
        }

        parent::__construct('Invalid command input', Command::INVALID);
    }
}
