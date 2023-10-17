<?php

namespace GustavPHP\Gustav\Controller;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response as InternalResponse;
use React\Stream\ReadableStreamInterface;

class Response implements StatusCodeInterface
{
    /**
     * Response constructor.
     *
     * @param int $status
     * @param array<string,string|array<string>> $headers
     * @param mixed $body
     * @return void
     */
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
    /**
     * Get body
     *
     * @return string|ReadableStreamInterface|StreamInterface
     */
    public function getBody(): string|ReadableStreamInterface|StreamInterface
    {
        return $this->body;
    }
    /**
     * Get headers
     *
     * @return array<string,string|array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
    /**
     * Merge another Response into this one.
     *
     * @param Response $response
     * @return Response
     */
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
    /**
     * Set body
     *
     * @param mixed $body
     * @return void
     */
    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }
    /**
     * Set headers
     *
     * @param array<string,string|array<string>> $headers
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
    /**
     * Set status
     *
     * @param int $status
     * @return void
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
