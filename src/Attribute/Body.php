<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Body
{
    protected bool $required = true;
    public function __construct(protected ?string $key = null)
    {
    }

    public function getKey(): string
    {
        return $this->key ?? throw new Exception('Body key is not set.');
    }

    public function hasKey(): bool
    {
        return $this->key !== null;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }
}
