<?php

namespace GustavPHP\Gustav\Router;

use GustavPHP\Gustav\Message\RequestInterface;

enum Method: string
{
    case GET = 'GET';
    case HEAD = 'HEAD';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case CONNECT = 'CONNECT';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';

    public static function fromRequest(RequestInterface $request): self
    {
        return Method::from($request->getMethod());
    }
}
