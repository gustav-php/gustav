<?php

namespace TorstenDittmann\Gustav\Router;

use Sabre\HTTP\Request;

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

    public static function fromRequest(Request $request): self
    {
        return Method::from($request->getMethod());
    }
}
