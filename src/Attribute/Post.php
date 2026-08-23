<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Router\Method;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Post extends Route
{
    public function __construct(string $path = '', ?string $name = null)
    {
        parent::__construct($path, Method::POST, $name);
    }
}
