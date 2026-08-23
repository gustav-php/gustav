<?php

namespace GustavPHP\Gustav\Router;

interface UrlGeneratorInterface
{
    /**
     * Generate a path for a named route.
     *
     * @param array<string,mixed> $parameters
     * @param array<string,mixed> $query
     */
    public function generate(string $name, array $parameters = [], array $query = []): string;
}
