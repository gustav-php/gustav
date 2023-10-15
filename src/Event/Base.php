<?php

namespace GustavPHP\Gustav\Event;

abstract class Base
{
    abstract public function handle(Payload $payload): void;
}
