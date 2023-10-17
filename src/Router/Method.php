<?php

namespace GustavPHP\Gustav\Router;

use Psr\Http\Message\ServerRequestInterface;

enum Method: string
{
    case CONNECT = 'CONNECT';
    case DELETE = 'DELETE';
    case GET = 'GET';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
    case POST = 'POST';
    case PUT = 'PUT';
    case TRACE = 'TRACE';

    /**
     * Return a Method from a Request.
     *
     * @param ServerRequestInterface $request
     * @return Method
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        return Method::from($request->getMethod());
    }
}
