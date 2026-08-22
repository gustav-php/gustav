<?php

namespace GustavPHP\Gustav\Auth;

use GustavPHP\Gustav\Auth\Exception\{InvalidAuthHeaderException, MissingAuthHeaderException};
use Psr\Http\Message\ServerRequestInterface;

readonly class ApiKeyAuth
{
    public const SOURCE_HEADER = 'header';
    public const SOURCE_QUERY = 'query';

    public function __construct(
        private string $key,
        private string $source
    ) {
    }

    /**
     * Extract API key from header.
     *
     * @param ServerRequestInterface $request
     * @param string $name Header name (e.g., 'X-API-Key')
     * @return self
     * @throws MissingAuthHeaderException
     * @throws InvalidAuthHeaderException
     */
    public static function fromHeader(ServerRequestInterface $request, string $name): self
    {
        $value = $request->getHeaderLine($name);

        if ($value === '') {
            throw new MissingAuthHeaderException("API key header '{$name}' is missing");
        }

        if (trim($value) === '') {
            throw new InvalidAuthHeaderException("API key in header '{$name}' is empty");
        }

        return new self(trim($value), self::SOURCE_HEADER);
    }

    /**
     * Extract API key from query parameter.
     *
     * @param ServerRequestInterface $request
     * @param string $name Query parameter name (e.g., 'api_key')
     * @return self
     * @throws MissingAuthHeaderException
     * @throws InvalidAuthHeaderException
     */
    public static function fromQuery(ServerRequestInterface $request, string $name): self
    {
        $params = $request->getQueryParams();

        if (!array_key_exists($name, $params)) {
            throw new MissingAuthHeaderException("API key query parameter '{$name}' is missing");
        }

        $value = $params[$name];

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidAuthHeaderException("API key in query parameter '{$name}' is empty or invalid");
        }

        return new self(trim($value), self::SOURCE_QUERY);
    }

    /**
     * Get the API key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the source ('header' or 'query').
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Check if key came from header.
     *
     * @return bool
     */
    public function isFromHeader(): bool
    {
        return $this->source === self::SOURCE_HEADER;
    }

    /**
     * Check if key came from query string.
     *
     * @return bool
     */
    public function isFromQuery(): bool
    {
        return $this->source === self::SOURCE_QUERY;
    }
}
