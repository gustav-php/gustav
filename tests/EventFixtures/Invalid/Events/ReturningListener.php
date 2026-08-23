<?php

namespace GustavPHP\Tests\EventFixtures\Invalid\Events;

use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\RecordedEvent;

#[Listener]
final readonly class ReturningListener
{
    public function __invoke(RecordedEvent $event): int
    {
        return 0;
    }
}
