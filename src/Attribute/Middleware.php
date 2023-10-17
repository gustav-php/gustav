<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Middleware\Base;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware
{
    public function __construct(protected string $class)
    {
        if (!is_subclass_of($class, Base::class)) {
            throw new \Exception("Class {$class} must extend " . Base::class);
        }
    }

    public function initialize(): Base
    {
        return new $this->class();
    }
}
