<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;
use GustavPHP\Gustav\Middleware\Base;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware
{
    /**
     * Middleware Attribute.
     *
     * @param Base $instance
     * @return void
     * @throws Exception
     */
    public function __construct(protected Base $instance)
    {
    }

    /**
     * @return Base
     */
    public function getInstance(): Base
    {
        return $this->instance;
    }
}
