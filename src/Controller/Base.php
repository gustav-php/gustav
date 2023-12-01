<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Traits\{Logger, Validate};
use GustavPHP\Gustav\{Application, Serializer, View};

use function json_encode;

use Latte\RuntimeException;
use LogicException;
use Throwable;

class Base
{
    use Logger;
    use Validate;

    /**
     * Returns a HTML Response.
     *
     * @param string $body
     * @param int $status
     * @return Response
     */
    protected function html(string $body, int $status = 200): Response
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
     * @param array<mixed>|object $data
     * @param int $status
     * @return Response
     */
    protected function json(array|object $data, int $status = 200): Response
    {
        return new Response(
            status: $status,
            body: json_encode($data),
            headers: ['Content-Type' => 'application/json']
        );
    }

    /**
     * Returns a Plaintext Response.
     *
     * @param string $body
     * @param int $status
     * @return Response
     */
    protected function plaintext(string $body, int $status = 200): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Returns a redirect Response.
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 301): Response
    {
        return new Response(
            status: $status,
            headers: ['Location' => $url]
        );
    }
    /**
     * Returns a Serializer Response.
     *
     * @param Serializer\Base $object
     * @param int $status
     * @return Response
     */
    protected function serialize(Serializer\Base $object, int $status = 200): Response
    {
        return new Response(
            status: $status,
            body: $object,
            headers: ['Content-Type' => 'application/json']
        );
    }

    /**
     * Returns a HTML View.
     *
     * @param string $template
     * @param array<mixed> $params
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function view(string $template, array $params = []): Response
    {
        if (Application::$configuration->views) {
            $template = Application::$configuration->views . DIRECTORY_SEPARATOR . $template;
        }

        $view = View::render($template, $params);

        return new Response(
            status: 200,
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
    protected function xml(string $body, int $status = 200): Response
    {
        return new Response(
            status: $status,
            body: $body,
            headers: ['Content-Type' => 'text/xml']
        );
    }
}
