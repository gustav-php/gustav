<?php

namespace GustavPHP\Gustav\Session;

interface SessionStoreInterface
{
    /**
     * Acquire exclusive access to one session for the current request.
     *
     * A missing session returns null unless $create is true. Every returned
     * lease must be released by the caller.
     */
    public function acquire(string $id, bool $create = false): ?SessionLeaseInterface;
}
