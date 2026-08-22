<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * Middleware Attribute.
     *
     * @param MiddlewareInterface $instance
     * @return void
     */
    public function __construct(protected MiddlewareInterface $instance)
    {
    }

    /**
     * @return MiddlewareInterface
     */
    public function getInstance(): MiddlewareInterface
    {
        return $this->instance;
    }
}
