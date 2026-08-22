<?php

namespace GustavPHP\Gustav\Auth;

interface Identity
{
    /**
     * Get the unique identifier for this identity.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Get the roles assigned to this identity.
     *
     * @return array<string>
     */
    public function getRoles(): array;
}
