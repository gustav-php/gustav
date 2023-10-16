<?php

namespace tests\Message\SAPI;

use PHPUnit\Framework\TestCase;
use GustavPHP\Gustav\Message\SAPI\Response;
use Sabre\HTTP\Response as SabreResponse;

class ResponseTest extends TestCase
{
    protected $sabreResponse;
    protected $response;

    protected function setUp(): void
    {
        $this->sabreResponse = $this->createMock(SabreResponse::class);
        $this->response = new Response($this->sabreResponse);
    }

    public function testGetBody()
    {
        $this->sabreResponse->method('getBody')->willReturn('body');
        $this->assertEquals('body', $this->response->getBody());
    }

    public function testGetHeader()
    {
        $this->sabreResponse->method('getHeader')->willReturn('header');
        $this->assertEquals('header', $this->response->getHeader('header'));
    }

    public function testGetHeaders()
    {
        $this->sabreResponse->method('getHeaders')->willReturn(['header1' => 'value1', 'header2' => 'value2']);
        $this->assertEquals(['header1' => 'value1', 'header2' => 'value2'], $this->response->getHeaders());
    }

    public function testGetStatus()
    {
        $this->sabreResponse->method('getStatus')->willReturn(200);
        $this->assertEquals(200, $this->response->getStatus());
    }

    public function testGetStatusText()
    {
        $this->sabreResponse->method('getStatusText')->willReturn('OK');
        $this->assertEquals('OK', $this->response->getStatusText());
    }
}
