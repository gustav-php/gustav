<?php

namespace GustavPHP\Tests\EventFixtures\Invalid\Events;

use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\{FailingEvent, RecordedEvent};

#[Listener]
final readonly class UnionListener
{
    public function __invoke(FailingEvent|RecordedEvent $event): void
    {
    }
}
