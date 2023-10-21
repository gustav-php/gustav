<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\DTO\Mapper;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Query
{
    protected ?Mapper $dto = null;
    protected bool $required = false;
    public function __construct(protected ?string $key = null)
    {
    }

    public function getDto(): Mapper
    {
        return $this->dto ?? throw new \Exception('DTO is not set.');
    }

    public function getKey(): string
    {
        return $this->key ?? throw new \Exception('Query key is not set.');
    }

    public function hasDto(): bool
    {
        return $this->dto !== null;
    }

    public function hasKey(): bool
    {
        return $this->key !== null;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setDto(Mapper $dto): self
    {
        $this->dto = $dto;

        return $this;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }
}
