<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * Middleware Attribute.
     *
     * @param class-string<MiddlewareInterface> $class
     * @return void
     */
    public function __construct(protected string $class)
    {
        if (!is_a($class, MiddlewareInterface::class, true)) {
            throw new InvalidArgumentException(
                "Middleware '{$class}' must implement " . MiddlewareInterface::class,
            );
        }
    }

    /**
     * @return class-string<MiddlewareInterface>
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
