<?php

namespace GustavPHP\Gustav;

use InvalidArgumentException;

final readonly class View
{
    /**
     * @param array<mixed>|object $data
     * @param array<string,string|array<string>> $headers
     */
    public function __construct(
        public string $template,
        public array|object $data = [],
        public int $status = 200,
        public array $headers = [],
    ) {
        if (trim($template) === '' || str_contains($template, "\0")) {
            throw new InvalidArgumentException('View template must be a non-empty logical name');
        }
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('View response status must be between 100 and 599');
        }
        foreach ($headers as $name => $values) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException('View response header names must be non-empty strings');
            }
            if (is_string($values)) {
                continue;
            }
            if (!is_array($values)) {
                throw new InvalidArgumentException('View response header values must be strings');
            }
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new InvalidArgumentException('View response header values must be strings');
                }
            }
        }
    }
}
