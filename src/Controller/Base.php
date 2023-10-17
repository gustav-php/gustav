<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Traits\Logger;
use GustavPHP\Gustav\Traits\Request;
use GustavPHP\Gustav\View;

class Base
{
    use Logger;
    use Request;

    /**
     * Returns a HTML Response.
     *
     * @param string $body
     * @param int $status
     * @return Response
     */
    protected function html(string $body, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/html']
        );
    }

    /**
     * Returns a JSON Response.
     *
     * @param array|\stdClass $data
     * @param int $status
     * @return Response
     */
    protected function json(array|\stdClass $data, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: \json_encode($data),
            headers: ['Content-Type' => 'application/json']
        );
    }

    /**
     * Returns a redirect Response.
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = Response::STATUS_FOUND): Response
    {
        return new Response(
            status: $status,
            headers: ['Location' => $url]
        );
    }

    /**
     * Returns a HTML View.
     *
     * @param string $template
     * @param array $params
     * @return Response
     * @throws \LogicException
     * @throws \Latte\RuntimeException
     * @throws \Throwable
     */
    protected function view(string $template, array $params = []): Response
    {
        $view = View::render($template, $params);

        return new Response(
            status: Response::STATUS_OK,
            body: $view,
            headers: ['Content-Type' => 'text/html']
        );
    }

    /**
     * Returns a XML Response.
     *
     * @param string $body
     * @param int $status
     * @return Response
     */
    protected function xml(string $body, int $status = Response::STATUS_OK): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/xml']
        );
    }
}
