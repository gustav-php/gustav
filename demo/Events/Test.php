<?php

namespace GustavPHP\Demo\Events;

use GustavPHP\Gustav\Attribute\Event;
use GustavPHP\Gustav\Event\{Base, Payload};

#[Event('test')]
class Test extends Base
{
    public function handle(Payload $payload): void
    {
        $this->log('Event: ' . $payload->getEvent());
    }
}
