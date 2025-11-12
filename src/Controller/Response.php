<?php

namespace GustavPHP\Gustav\Controller;

use GustavPHP\Gustav\Serializer;
use InvalidArgumentException;
use Nyholm\Psr7\Response as Psr7Response;

class Response
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
        protected int $status = 200,
        protected array $headers = [],
        protected mixed $body = '',
    ) {
    }
    /**
     * Build a Psr7Response from the Response.
     *
     * @return Psr7Response
     */
    public function build(): Psr7Response
    {
        return new Psr7Response(
            $this->status,
            $this->headers,
            $this->body
        );
    }
    /**
     * Build a Response with a JSON body.
     *
     * @return Psr7Response
     */
    public function buildHtml(): Psr7Response
    {
        return new Psr7Response(
            $this->status,
            array_merge($this->headers, ['Content-Type' => 'text/html']),
            $this->body
        );
    }
    /**
     * Build a Response with a JSON body.
     *
     * @return Psr7Response
     * @throws InvalidArgumentException
     */
    public function buildJson(): Psr7Response
    {
        return new Psr7Response(
            $this->status,
            array_merge($this->headers, ['Content-Type' => 'application/json']),
            (string) json_encode($this->body)
        );
    }
    /**
     * Build a Response with a plaintext body.
     *
     * @return Psr7Response
     */
    public function buildPlaintext(): Psr7Response
    {
        return new Psr7Response(
            $this->status,
            array_merge($this->headers, ['Content-Type' => 'text/plain']),
            $this->body
        );
    }
    /**
     * Get body
     *
     * @return mixed
     */
    public function getBody(): mixed
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

    public function getSerializer(): Serializer\Base|false
    {
        if (is_subclass_of($this->body, Serializer\Base::class)) {
            /**
             * @var Serializer\Base
             */
            return $this->body;
        }
        return false;
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
        $this->headers = array_merge($this->headers, $response->headers);

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
