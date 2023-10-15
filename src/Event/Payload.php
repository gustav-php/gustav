<?php

namespace GustavPHP\Gustav\Event;

class Payload
{
    public function __construct(
        protected string $event,
        protected array $data = []
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getEvent(): string
    {
        return $this->event;
    }
}
