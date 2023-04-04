<?php

namespace TorstenDittmann\Gustav;

use TorstenDittmann\Gustav\Message\DriverInterface;
use TorstenDittmann\Gustav\Message\SAPI;

class Configuration
{
    public function __construct(
        public readonly string $mode = 'development',
        public readonly DriverInterface $driver = new SAPI\Driver(),
        public readonly string $context = Context::class
    ) {
    }
}
