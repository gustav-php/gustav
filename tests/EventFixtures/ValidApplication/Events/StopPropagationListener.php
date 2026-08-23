<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

use GustavPHP\Gustav\Attribute\Listener;

#[Listener(priority: 100)]
final readonly class StopPropagationListener
{
    public function __invoke(StoppableEvent $event): void
    {
        $event->listeners[] = 'stop';
        $event->stop();
    }
}
