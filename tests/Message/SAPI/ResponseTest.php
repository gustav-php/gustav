<?php

namespace tests\Message\SAPI;

use PHPUnit\Framework\TestCase;
use GustavPHP\Gustav\Message\SAPI\Response;

class ResponseTest extends TestCase
{
    protected $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function testGetBody()
    {
        $this->response->setBody('body');
        $this->assertEquals('body', $this->response->getBody());
    }

    public function testGetHeader()
    {
        $this->response->setHeader('header', 'header value');
        $this->assertEquals('header value', $this->response->getHeader('header'));
    }

    public function testGetHeaders()
    {
        $this->response->setHeaders(['header1' => 'value1', 'header2' => 'value2']);
        $this->assertEquals(['header1' => 'value1', 'header2' => 'value2'], $this->response->getHeaders());
    }

    public function testGetStatus()
    {
        $this->response->setStatus(200);
        $this->assertEquals(200, $this->response->getStatus());
    }

    public function testGetStatusText()
    {
        $this->response->setStatusText('OK');
        $this->assertEquals('OK', $this->response->getStatusText());
    }
}
