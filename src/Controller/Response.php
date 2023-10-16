<?php

namespace GustavPHP\Gustav\Controller;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response as ReactResponse;
use React\Stream\ReadableStreamInterface;

class Response implements StatusCodeInterface
{
    public function __construct(
        protected int $status = ReactResponse::STATUS_OK,
        protected array $headers = [],
        protected mixed $body = '',
    ) {
    }
    public function build(): ReactResponse
    {
        return new ReactResponse(
            $this->status,
            $this->headers,
            $this->body
        );
    }
    public function buildHtml(): ReactResponse
    {
        return ReactResponse::html($this->body);
    }
    public function buildJson(): ReactResponse
    {
        return ReactResponse::json($this->body);
    }
    public function buildPlaintext(): ReactResponse
    {
        return ReactResponse::plaintext($this->body);
    }
    public function getBody(): string|ReadableStreamInterface|StreamInterface
    {
        return $this->body;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }
    public function getStatus(): int
    {
        return $this->status;
    }
    public function merge(Response $response): self
    {
        if ($response->status) {
            $this->status = $response->status;
        }
        if ($response->body) {
            $this->body = $response->body;
        }
        $this->headers = array_merge($this->headers ?? [], $response->headers ?? []);

        return $this;
    }
    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
