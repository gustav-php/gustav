<?php

namespace GustavPHP\Tests\SessionStoreFixtures\ValidApplication\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;
use GustavPHP\Gustav\Session\{SessionLeaseInterface, SessionStoreInterface};
use GustavPHP\Tests\SessionFixtures\MemorySessionStore;

#[Service(as: SessionStoreInterface::class, lifetime: Lifetime::Singleton)]
final class DiscoveredSessionStore extends MemorySessionStore
{
    public static int $uses = 0;

    public function acquire(string $id, bool $create = false): ?SessionLeaseInterface
    {
        self::$uses++;

        return parent::acquire($id, $create);
    }
}
