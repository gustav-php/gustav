<?php

namespace GustavPHP\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @param array{
     *     headers?: array<string, string|array<string>>,
     *     query?: array<string, mixed>,
     *     form_params?: array<string, mixed>,
     *     json?: array<string, mixed>|object,
     *     cookies?: array<string, string>,
     *     body?: string
     * } $options
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? '';
        $parsedBody = null;

        if (array_key_exists('form_params', $options)) {
            $parsedBody = $options['form_params'];
        }
        if (array_key_exists('json', $options)) {
            $parsedBody = $options['json'];
            $body = (string) json_encode($parsedBody);
            $headers['Content-Type'] = 'application/json';
        }

        $request = new ServerRequest($method, $uri, $headers, $body);
        if (array_key_exists('query', $options)) {
            $request = $request->withQueryParams($options['query']);
        }
        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }
        if (array_key_exists('cookies', $options)) {
            $request = $request->withCookieParams($options['cookies']);
        }

        return createApplication()->handle($request);
    }
}
