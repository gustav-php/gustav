<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\View;

class Base
{
    protected function html(string $body, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/html']
        );
    }
    protected function json(array|\stdClass $data, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: \json_encode($data),
            headers: ['Content-Type' => 'application/json']
        );
    }
    protected function redirect(string $url, int $status = Response::STATUS_FOUND): Response
    {
        return new Response(
            status: $status,
            headers: ['Location' => $url]
        );
    }
    protected function view(string $template, array $params): Response
    {
        $view = View::render($template, $params);

        return new Response(
            status: Response::STATUS_OK,
            body: $view,
            headers: ['Content-Type' => 'text/html']
        );
    }

    protected function xml(string $body, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/xml']
        );
    }
}
