<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Router\Method;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        protected readonly string $path = '',
        protected readonly Method $method = Method::GET,
        protected readonly ?string $name = null,
    ) {
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
