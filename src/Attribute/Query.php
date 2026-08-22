<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Query
{
    public function __construct(private ?string $key = null)
    {
    }

    public function getKey(): string
    {
        return $this->key ?? throw new Exception('Query key is not set.');
    }

    public function hasKey(): bool
    {
        return $this->key !== null;
    }

}
