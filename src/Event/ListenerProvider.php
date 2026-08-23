<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Service\Container;
use LogicException;
use Psr\EventDispatcher\ListenerProviderInterface;

/** @internal */
final readonly class ListenerProvider implements ListenerProviderInterface
{
    /** @param list<ListenerDefinition> $listeners */
    public function __construct(
        private Container $services,
        private array $listeners,
    ) {
    }

    /** @return iterable<callable(object): void> */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $definition) {
            $eventType = $definition->event;
            if (!$event instanceof $eventType) {
                continue;
            }

            yield function (object $dispatched) use ($definition): void {
                $listener = $this->services->get($definition->listener);
                if (!is_callable($listener)) {
                    throw new LogicException(
                        "Resolved event listener '{$definition->listener}' is not callable",
                    );
                }
                $listener($dispatched);
            };
        }
    }
}
