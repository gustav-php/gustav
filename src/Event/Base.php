<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Traits\Validate;

abstract class Base
{
    use Validate;

    abstract public function handle(Payload $payload): void;
}
