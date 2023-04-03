<?php

namespace TorstenDittmann\Gustav\Message\SAPI;

use TorstenDittmann\Gustav\Message\DriverInterface;
use TorstenDittmann\Gustav\Message\RequestInterface;
use TorstenDittmann\Gustav\Message\ResponseInterface;

class Driver implements DriverInterface
{
    public static function buildRequest(): RequestInterface
    {
        return new Request();
    }
    public static function buildResponse(): ResponseInterface
    {
        return new Response();
    }
}
