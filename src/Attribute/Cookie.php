<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Cookie
{
    public function __construct(private ?string $key = null)
    {
    }

    public function getKey(): string
    {
        return $this->key ?? throw new Exception('Cookie key is not set.');
    }

    public function hasKey(): bool
    {
        return $this->key !== null;
    }

}
