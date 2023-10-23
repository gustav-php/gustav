<?php

namespace GustavPHP\Gustav\Event;

use GustavPHP\Gustav\Traits\{Logger, Validate};

abstract class Base
{
    use Logger;
    use Validate;

    abstract public function handle(Payload $payload): void;
}
