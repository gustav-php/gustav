<?php

namespace GustavPHP\Demo\Events;

use GustavPHP\Gustav\Attribute\Event;
use GustavPHP\Gustav\Event\Base;
use GustavPHP\Gustav\Event\Payload;
use GustavPHP\Gustav\Logger\Logger;

#[Event('test')]
class Test extends Base
{
    public function handle(Payload $payload): void
    {
        Logger::log('Event: ' . $payload->getEvent());
    }
}
