<?php

namespace GustavPHP\Tests\SessionFixtures;

use GustavPHP\Gustav\Session\{SessionLeaseInterface, SessionRecord};
use LogicException;

final class MemorySessionLease implements SessionLeaseInterface
{
    private bool $released = false;

    public function __construct(
        private readonly MemorySessionStore $store,
        private readonly string $id,
    ) {
    }

    public function destroy(): void
    {
        $this->assertActive();
        $this->store->delete($this->id);
    }

    public function read(): ?SessionRecord
    {
        $this->assertActive();

        return $this->store->read($this->id);
    }

    public function release(): void
    {
        $this->released = true;
    }

    public function write(SessionRecord $record): void
    {
        $this->assertActive();
        $this->store->write($this->id, $record);
    }

    private function assertActive(): void
    {
        if ($this->released) {
            throw new LogicException('Memory session lease has already been released');
        }
    }
}
