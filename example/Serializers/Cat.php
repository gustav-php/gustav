<?php

namespace GustavPHP\Example\Serializers;

use GustavPHP\Gustav\Attribute\Serializer\Exclude;
use GustavPHP\Gustav\Serializer;

class Cat extends Serializer\Base
{
    public string $id;
    public string $name;

    #[Exclude]
    public string $secret = 'meow';

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
