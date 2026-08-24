<?php

namespace GustavPHP\Tests\SessionFixtures;

use GustavPHP\Gustav\Session\{SessionLeaseInterface, SessionRecord, SessionStoreInterface};

class MemorySessionStore implements SessionStoreInterface
{
    public int $acquisitions = 0;

    /** @var array<string,SessionRecord|null> */
    private array $records = [];

    public function acquire(string $id, bool $create = false): ?SessionLeaseInterface
    {
        $this->acquisitions++;
        if (!array_key_exists($id, $this->records)) {
            if (!$create) {
                return null;
            }
            $this->records[$id] = null;
        }

        return new MemorySessionLease($this, $id);
    }

    public function delete(string $id): void
    {
        unset($this->records[$id]);
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_keys($this->records);
    }

    public function read(string $id): ?SessionRecord
    {
        return $this->records[$id] ?? null;
    }

    public function write(string $id, SessionRecord $record): void
    {
        $this->records[$id] = $record;
    }
}
