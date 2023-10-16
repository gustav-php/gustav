<?php

namespace tests\Message\SAPI;

use PHPUnit\Framework\TestCase;
use GustavPHP\Gustav\Message\SAPI\Request;

class RequestTest extends TestCase
{
    protected $request;

    protected function setUp(): void
    {
        $this->request = new Request();
    }

    public function testGetBody()
    {
        $this->request->setBody('body');
        $this->assertEquals('body', $this->request->getBody());
    }

    public function testGetHeader()
    {
        $this->request->setHeader('header', 'header value');
        $this->assertEquals('header value', $this->request->getHeader('header'));
    }

    public function testGetHeaders()
    {
        $this->request->setHeaders(['header1' => 'value1', 'header2' => 'value2']);
        $this->assertEquals(['header1' => 'value1', 'header2' => 'value2'], $this->request->getHeaders());
    }

    public function testGetMethod()
    {
        $this->request->setMethod('GET');
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testGetPath()
    {
        $this->request->setPath('/path');
        $this->assertEquals('/path', $this->request->getPath());
    }
}
