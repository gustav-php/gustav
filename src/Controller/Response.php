<?php

namespace GustavPHP\Gustav\Controller;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response as InternalResponse;
use React\Stream\ReadableStreamInterface;

class Response implements StatusCodeInterface
{
    public function __construct(
        protected int $status = InternalResponse::STATUS_OK,
        protected array $headers = [],
        protected mixed $body = '',
    ) {
    }
    /**
     * Build a InternalResponse from the Response.
     *
     * @return InternalResponse
     */
    public function build(): InternalResponse
    {
        return new InternalResponse(
            $this->status,
            $this->headers,
            $this->body
        );
    }
    /**
     * Build a Response with a JSON body.
     *
     * @return InternalResponse
     */
    public function buildHtml(): InternalResponse
    {
        return InternalResponse::html($this->body);
    }
    /**
     * Build a Response with a JSON body.
     *
     * @return InternalResponse
     * @throws \InvalidArgumentException
     */
    public function buildJson(): InternalResponse
    {
        return InternalResponse::json($this->body);
    }
    /**
     * Build a Response with a plaintext body.
     *
     * @return InternalResponse
     */
    public function buildPlaintext(): InternalResponse
    {
        return InternalResponse::plaintext($this->body);
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
