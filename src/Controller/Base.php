<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\View;

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
    protected function redirect(string $url, int $code = 302): Response
    {
        return new Response(
            code: $code,
            headers: ['Location' => $url]
        );
    }
    protected function view(string $template, array $params): Response
    {
        $view = View::render($template, $params);

        return new Response(
            code: 200,
            body: $view,
            headers: ['Content-Type' => 'text/html']
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
