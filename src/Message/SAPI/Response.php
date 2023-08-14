<?php

namespace GustavPHP\Gustav\Message\SAPI;

use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Message\ResponseInterface;

class Response implements ResponseInterface
{
    protected \Sabre\HTTP\Response $response;
    public function __construct()
    {
        $this->response = new \Sabre\HTTP\Response();
    }
    public function getBody()
    {
        return $this->response->getBody();
    }
    public function getHeader(string $name): ?string
    {
        return $this->response->getHeader($name);
    }
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }
    public function getStatus(): int
    {
        return $this->response->getStatus();
    }
    public function getStatusText(): string
    {
        return $this->response->getStatusText();
    }
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }
    public function importControllerResponse(Controller\Response $response): void
    {
        $this->setBody($response->body);
        $this->setStatus($response->code);
        $this->setHeaders($response->headers);
    }
    public function removeHeader(string $name): bool
    {
        return $this->response->removeHeader($name);
    }

    public function send(): void
    {
        \Sabre\HTTP\Sapi::sendResponse($this->response);
    }
    public function setBody(string $body): void
    {
        $this->response->setBody($body);
    }
    public function setHeader(string $name, $value): void
    {
        $this->response->setHeader($name, $value);
    }
    public function setHeaders(array $headers): void
    {
        $this->response->setHeaders($headers);
    }
    public function setStatus(int $status): void
    {
        $this->response->setStatus($status);
    }
}
