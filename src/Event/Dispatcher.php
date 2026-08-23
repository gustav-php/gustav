<?php

namespace GustavPHP\Gustav\Event;

use Psr\EventDispatcher\{EventDispatcherInterface, ListenerProviderInterface, StoppableEventInterface};

/** @internal Inject EventDispatcherInterface in application code. */
final readonly class Dispatcher implements EventDispatcherInterface
{
    public function __construct(private ListenerProviderInterface $listeners)
    {
    }

    public function dispatch(object $event): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }

        return $event;
    }
}
