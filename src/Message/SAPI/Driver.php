<?php

namespace GustavPHP\Gustav\Message\SAPI;

use GustavPHP\Gustav\Message\DriverInterface;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Message\ResponseInterface;

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
