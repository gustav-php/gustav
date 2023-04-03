<?php

namespace TorstenDittmann\Gustav;

class Configuration
{
    public function __construct(
        public readonly string $mode = 'development'
    ) {
    }
}
