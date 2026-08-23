<?php

namespace GustavPHP\Gustav\Config\Exception;

use GustavPHP\Gustav\Config\Violation;
use InvalidArgumentException;
use RuntimeException;

final class ConfigurationException extends RuntimeException
{
    /**
     * @param non-empty-list<Violation> $violations
     */
    public function __construct(private readonly array $violations)
    {
        if ($violations === []) {
            throw new InvalidArgumentException('Configuration exception requires at least one violation');
        }

        parent::__construct(
            "Application configuration is invalid:\n" . implode("\n", array_map(
                fn (Violation $violation): string => sprintf(
                    '- %s (%s::$%s): %s',
                    $violation->variable,
                    $violation->configuration,
                    $violation->property,
                    $violation->message,
                ),
                $violations,
            )),
        );
    }

    /** @return non-empty-list<Violation> */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
