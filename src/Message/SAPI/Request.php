<?php

namespace TorstenDittmann\Gustav\Message\SAPI;

use TorstenDittmann\Gustav\Message\RequestInterface;

class Request implements RequestInterface
{
    protected \Sabre\HTTP\Request $request;
    public function __construct()
    {
        $this->request = \Sabre\HTTP\Sapi::getRequest();
    }
    public function getBody(): mixed
    {
        return $this->request->getBody();
    }
    public function setBody($body): void
    {
        $this->request->setBody($body);
    }
    public function getHeader(string $name): ?string
    {
        return $this->request->getHeader($name);
    }
    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }
    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }
    public function setHeader(string $name, $value): void
    {
        $this->request->setHeader($name, $value);
    }
    public function setHeaders(array $headers): void
    {
        $this->request->setHeaders($headers);
    }
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }
    public function setMethod(string $method): void
    {
        $this->request->setMethod($method);
    }
    public function getUrl(): string
    {
        return $this->request->getUrl();
    }
    public function setUrl(string $url): void
    {
        $this->request->setUrl($url);
    }
    public function getPath(): string
    {
        return $this->request->getPath();
    }
    public function getQueryParameters(): array
    {
        return $this->request->getQueryParameters();
    }
    public function getPostData(): array
    {
        return $this->request->getPostData();
    }
    public function setPostData(array $postData): void
    {
        $this->request->setPostData($postData);
    }
}
