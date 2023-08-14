<?php

namespace GustavPHP\Gustav\Controller;

class Response
{
    public function __construct(
        public int $code = 200,
        public string $body = '',
        public array $headers = []
    ) {
    }
}
