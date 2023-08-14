<?php

namespace GustavPHP\Gustav\Message;

use GustavPHP\Gustav\Controller;

interface ResponseInterface
{
    public function getBody();
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function getStatus(): int;
    public function getStatusText(): string;
    public function hasHeader(string $name): bool;
    public function importControllerResponse(Controller\Response $response): void;
    public function removeHeader(string $name): bool;
    public function send(): void;
    public function setBody(string $body): void;
    public function setHeader(string $name, $value): void;
    public function setHeaders(array $headers): void;
    public function setStatus(int $status): void;
}
