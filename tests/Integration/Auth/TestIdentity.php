<?php

namespace GustavPHP\Tests\Integration\Auth;

use GustavPHP\Gustav\Auth\Identity;

readonly class TestIdentity implements Identity
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        private string $identifier,
        private array $roles = [],
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
