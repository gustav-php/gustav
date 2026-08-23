<?php

namespace GustavPHP\Gustav\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Stringable;
use Throwable;

final readonly class RequestId implements Stringable
{
    public const ATTRIBUTE = self::class;

    public const HEADER = 'X-Request-ID';

    private const PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9._-]{0,127}\z/D';

    private function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $values = $request->getHeader(self::HEADER);

        if (count($values) === 1 && self::isValid($values[0])) {
            return new self($values[0]);
        }

        return self::generate();
    }

    public static function fromString(string $value): self
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException(
                'Request ID must contain 1 to 128 letters, digits, dots, underscores, or hyphens',
            );
        }

        return new self($value);
    }

    private static function generate(): self
    {
        try {
            $value = bin2hex(random_bytes(16));
        } catch (Throwable) {
            $value = substr(hash('sha256', uniqid('', true)), 0, 32);
        }

        return new self($value);
    }

    private static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }
}
