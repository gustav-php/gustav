<?php

namespace TorstenDittmann\Gustav\Attribute;

use Attribute;
use TorstenDittmann\Gustav\Middleware\Base;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware
{
    public function __construct(protected string $class, protected bool $singleton = false)
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
