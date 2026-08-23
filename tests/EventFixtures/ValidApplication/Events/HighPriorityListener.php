<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Tests\EventFixtures\ValidApplication\Services\ListenerDependency;

#[Listener(priority: 100)]
final readonly class HighPriorityListener
{
    private int $id;

    public function __construct(private ListenerDependency $dependency)
    {
        static $next = 0;

        $this->id = ++$next;
    }

    public function __invoke(RecordedEvent $event): void
    {
        $event->record('high', $this->id, $this->dependency->id);
    }
}
