<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Traits\Logger;

abstract class Base
{
    use Logger;
    abstract public function handle(Payload $payload): void;
}
