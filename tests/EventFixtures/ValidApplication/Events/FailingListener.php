<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

use GustavPHP\Gustav\Attribute\Listener;
use RuntimeException;

#[Listener]
final readonly class FailingListener
{
    public function __invoke(FailingEvent $event): void
    {
        throw new RuntimeException('secret listener failure');
    }
}
