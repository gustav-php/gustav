<?php

namespace GustavPHP\Gustav\Message;

interface ResponseInterface
{
    public function getBody();
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function getStatus(): int;
    public function getStatusText(): string;
    public function hasHeader(string $name): bool;
    public function removeHeader(string $name): bool;
    public function send(): void;
    public function setBody($body): void;
    public function setHeader(string $name, $value): void;
    public function setHeaders(array $headers): void;
    public function setStatus($status): void;
}
