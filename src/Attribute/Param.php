<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Param
{
    protected ?string $parameter;

    public function __construct(protected ?string $name = null)
    {
    }

    public function getName(): string
    {
        return $this->name ?? throw new \Exception('Param name is not set.');
    }

    public function getParameter(): string
    {
        if ($this->parameter === null) {
            throw new \Exception("Parameter {$this->name} has not been initialized");
        }
        return $this->parameter;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function setParameter(string $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }
}
