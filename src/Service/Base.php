<?php

namespace TorstenDittmann\Gustav\Service;

class Base
{
    private ?object $instance = null;

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function initialize(string $class, ...$args)
    {
        $this->instance = new $class(...$args);
    }
}
