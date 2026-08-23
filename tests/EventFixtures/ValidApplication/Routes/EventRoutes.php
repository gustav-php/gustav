<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\{FailingEvent, RecordedEvent};
use Psr\EventDispatcher\EventDispatcherInterface;

#[Controller('/events')]
final class EventRoutes
{
    public function __construct(private readonly EventDispatcherInterface $events)
    {
    }

    #[Get]
    /** @return array{records:list<array{listener:string,listenerId:int,dependencyId:int}>} */
    public function dispatch(): array
    {
        $event = new RecordedEvent();
        $this->events->dispatch($event);

        return ['records' => $event->records];
    }

    #[Get('/fail')]
    /** @return array{} */
    public function fail(): array
    {
        $this->events->dispatch(new FailingEvent());

        return [];
    }
}
