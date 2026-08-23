<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\{Serializer, View};
use GustavPHP\Gustav\Traits\Validate;

class Base
{
    use Validate;

    /**
     * Returns a HTML Response.
     *
     * @param string $body
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return Response
     */
    protected function html(
        string $body,
        int $status = 200,
        array $headers = []
    ): Response {
        return new Response(
            status: $status,
            body: $body,
            headers: array_merge($headers, ['Content-Type' => 'text/html'])
        );
    }

    /**
     * Returns a JSON Response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return Response
     */
    protected function json(
        mixed $data,
        int $status = 200,
        array $headers = []
    ): Response {
        return new Response(
            status: $status,
            body: $data,
            headers: $headers,
            format: ResponseFormat::Json,
        );
    }

    /**
     * Returns a Plaintext Response.
     *
     * @param string $body
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return Response
     */
    protected function plaintext(
        string $body,
        int $status = 200,
        array $headers = []
    ): Response {
        return new Response(
            status: $status,
            body: $body,
            headers: array_merge($headers, ['Content-Type' => 'text/plain'])
        );
    }

    /**
     * Returns a redirect Response.
     *
     * @param string $url
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return Response
     */
    protected function redirect(
        string $url,
        int $status = 301,
        array $headers = []
    ): Response {
        return new Response(
            status: $status,
            headers: array_merge($headers, ['Location' => $url])
        );
    }
    /**
     * Returns a Serializer Response.
     *
     * @param Serializer\Base $object
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return Response
     */
    protected function serialize(
        Serializer\Base $object,
        int $status = 200,
        array $headers = []
    ): Response {
        return new Response(
            status: $status,
            body: $object,
            headers: $headers,
            format: ResponseFormat::Json,
        );
    }

    /**
     * Returns a HTML View.
     *
     * @param string $template
     * @param array<mixed>|object $params
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @return View
     */
    protected function view(
        string $template,
        array|object $params = [],
        int $status = 200,
        array $headers = []
    ): View {
        return new View(
            template: $template,
            data: $params,
            status: $status,
            headers: $headers,
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
