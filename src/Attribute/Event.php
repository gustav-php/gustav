<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Event
{
    public function __construct(protected string $event)
    {
    }

    public function getEvent(): string
    {
        return $this->event;
    }
}
