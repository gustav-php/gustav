<?php

namespace tests\Message\SAPI;

use PHPUnit\Framework\TestCase;
use GustavPHP\Gustav\Message\SAPI\Request;
use Sabre\HTTP\Request as SabreRequest;

class RequestTest extends TestCase
{
    protected $sabreRequest;
    protected $request;

    protected function setUp(): void
    {
        $this->sabreRequest = $this->createMock(SabreRequest::class);
        $this->request = new Request($this->sabreRequest);
    }

    public function testGetBody()
    {
        $this->sabreRequest->method('getBody')->willReturn('body');
        $this->assertEquals('body', $this->request->getBody());
    }

    public function testGetHeader()
    {
        $this->sabreRequest->method('getHeader')->willReturn('header');
        $this->assertEquals('header', $this->request->getHeader('header'));
    }

    public function testGetHeaders()
    {
        $this->sabreRequest->method('getHeaders')->willReturn(['header1' => 'value1', 'header2' => 'value2']);
        $this->assertEquals(['header1' => 'value1', 'header2' => 'value2'], $this->request->getHeaders());
    }

    public function testGetMethod()
    {
        $this->sabreRequest->method('getMethod')->willReturn('GET');
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testGetPath()
    {
        $this->sabreRequest->method('getPath')->willReturn('/path');
        $this->assertEquals('/path', $this->request->getPath());
    }
}
