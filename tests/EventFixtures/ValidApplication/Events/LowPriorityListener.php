<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

use GustavPHP\Gustav\Attribute\Listener;
use GustavPHP\Tests\EventFixtures\ValidApplication\Services\ListenerDependency;

#[Listener(priority: -100)]
final readonly class LowPriorityListener
{
    private int $id;

    public function __construct(private ListenerDependency $dependency)
    {
        static $next = 0;

        $this->id = ++$next;
    }

    public function __invoke(RecordableEvent $event): void
    {
        $event->record('low', $this->id, $this->dependency->id);
    }
}
