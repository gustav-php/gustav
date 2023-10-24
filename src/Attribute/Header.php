<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Header
{
    protected bool $required = false;
    public function __construct(protected ?string $name = null)
    {
    }

    public function getName(): string
    {
        return $this->name ?? throw new Exception('Query key is not set.');
    }

    public function hasName(): bool
    {
        return $this->name !== null;
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
