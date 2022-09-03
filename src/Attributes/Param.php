<?php

namespace TorstenDittmann\Gustav\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Param
{
    protected ?string $parameter;
    protected ?bool $required;

    public function __construct(protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function setParameter(string $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }
}
