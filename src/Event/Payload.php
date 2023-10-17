<?php

namespace GustavPHP\Gustav\Event;

class Payload
{
    public function __construct(
        protected string $event,
        protected array $data = []
    ) {
    }

    /**
     * Get the payload data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }
}
