<?php

namespace GustavPHP\Tests\EventFixtures\Invalid\Events;

use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\RecordedEvent;

#[Listener]
final readonly class NullableListener
{
    public function __invoke(?RecordedEvent $event): void
    {
    }
}
