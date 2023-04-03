<?php

namespace TorstenDittmann\Gustav\Message;

interface RequestInterface
{
    public function getBody(): mixed;
    public function setBody($body): void;
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function setHeader(string $name, $value): void;
    public function setHeaders(array $headers): void;
    public function getMethod(): string;
    public function setMethod(string $method): void;
    public function getUrl(): string;
    public function setUrl(string $url): void;
    public function getPath(): string;
    public function getQueryParameters(): array;
    public function getPostData(): array;
    public function setPostData(array $postData): void;
}
