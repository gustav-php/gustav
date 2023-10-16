<?php

namespace tests\Message\SAPI;

use PHPUnit\Framework\TestCase;
use GustavPHP\Gustav\Message\SAPI\Driver;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Message\ResponseInterface;

class DriverTest extends TestCase
{
    public function testBuildRequest()
    {
        $request = Driver::buildRequest();
        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function testBuildResponse()
    {
        $response = Driver::buildResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
