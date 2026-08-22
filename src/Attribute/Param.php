<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Param
{
    public function __construct(private ?string $name = null)
    {
    }

    public function getName(): string
    {
        return $this->name ?? throw new Exception('Param name is not set.');
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

}
