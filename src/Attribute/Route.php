<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;
use GustavPHP\Gustav\Router\Method;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @var null|string
     */
    protected ?string $class;
    /**
     * @var null|string
     */
    protected ?string $function;
    /**
     * @var array<string,int>
     */
    protected array $placeholders = [];

    public function __construct(protected string $path, protected Method $method = Method::GET)
    {
    }

    public function addPlaceholder(string $key, int $index): void
    {
        $this->placeholders[$key] = $index;
    }

    public function getClass(): string
    {
        if ($this->class === null) {
            throw new Exception('Class not set');
        }
        return $this->class;
    }

    public function getFunction(): string
    {
        if ($this->function === null) {
            throw new Exception('Function not set');
        }
        return $this->function;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string,int>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function setFunction(string $function): self
    {
        $this->function = $function;

        return $this;
    }
}
