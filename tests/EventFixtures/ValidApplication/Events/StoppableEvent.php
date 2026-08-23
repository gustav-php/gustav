<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

use Psr\EventDispatcher\StoppableEventInterface;

final class StoppableEvent implements StoppableEventInterface
{
    /** @var list<string> */
    public array $listeners = [];

    private bool $stopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }
}
