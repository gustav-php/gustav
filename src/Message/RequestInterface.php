<?php

namespace GustavPHP\Gustav\Message;

interface RequestInterface
{
    public function getBody(): mixed;
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function getMethod(): string;
    public function getPath(): string;
    public function getPostData(): array;
    public function getQueryParameters(): array;
    public function getUrl(): string;
    public function hasHeader(string $name): bool;
    public function setBody($body): void;
    public function setHeader(string $name, $value): void;
    public function setHeaders(array $headers): void;
    public function setMethod(string $method): void;
    public function setPostData(array $postData): void;
    public function setUrl(string $url): void;
}
