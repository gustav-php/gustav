<?php

namespace TorstenDittmann\Gustav\Message;

interface DriverInterface
{
    public static function buildRequest(): RequestInterface;
    public static function buildResponse(): ResponseInterface;
}
