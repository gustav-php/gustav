<?php

namespace GustavPHP\Gustav\Controller;

class Base
{
    protected function html(string $body, int $code = 200): Response
    {
        return new Response(
            code: $code,
            body: $body,
            headers: ['Content-Type' => 'text/html']
        );
    }
    protected function json(array|\stdClass $data, int $code = 200): Response
    {
        return new Response(
            code: $code,
            body: \json_encode($data),
            headers: ['Content-Type' => 'application/json']
        );
    }

    protected function xml(string $body, int $code = 200): Response
    {
        return new Response(
            code: $code,
            body: $body,
            headers: ['Content-Type' => 'text/xml']
        );
    }
}
