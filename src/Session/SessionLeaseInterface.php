<?php

namespace GustavPHP\Gustav\Session;

interface SessionLeaseInterface
{
    /** Delete the current record while retaining the lease until release. */
    public function destroy(): void;

    /** Read the current record, or null when this is a newly created lease. */
    public function read(): ?SessionRecord;

    /** Release exclusive access. Implementations must make this idempotent. */
    public function release(): void;

    /** Replace the complete record atomically before the lease is released. */
    public function write(SessionRecord $record): void;
}
