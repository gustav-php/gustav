<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Tests\EventFixtures\ValidApplication\Events\{FailingEvent, RecordedEvent};
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventRoutes extends Controller\Base
{
    public function __construct(private readonly EventDispatcherInterface $events)
    {
    }

    #[Route('/events')]
    /** @return array{records:list<array{listener:string,listenerId:int,dependencyId:int}>} */
    public function dispatch(): array
    {
        $event = new RecordedEvent();
        $this->events->dispatch($event);

        return ['records' => $event->records];
    }

    #[Route('/events/fail')]
    /** @return array{} */
    public function fail(): array
    {
        $this->events->dispatch(new FailingEvent());

        return [];
    }
}
