<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Commands;

use GustavPHP\Gustav\Attribute\Command;
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\RecordedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Command('events:dispatch', description: 'Dispatch a typed event')]
final readonly class DispatchEvent
{
    public function __construct(
        private EventDispatcherInterface $events,
        private OutputInterface $output,
    ) {
    }

    public function __invoke(): void
    {
        $event = new RecordedEvent();
        $this->events->dispatch($event);
        $this->output->writeln(implode(',', array_column($event->records, 'listener')));
    }
}
