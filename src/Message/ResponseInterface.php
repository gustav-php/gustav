<?php

namespace GustavPHP\Gustav\Message;

interface ResponseInterface
{
    public function getStatus(): int;
    public function getStatusText(): string;
    public function setStatus($status): void;
    public function getBody();
    public function setBody($body): void;
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function setHeader(string $name, $value): void;
    public function setHeaders(array $headers): void;
    public function removeHeader(string $name): bool;
    public function send(): void;
}
