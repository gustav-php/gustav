<?php

namespace GustavPHP\Example\Serializers;

use GustavPHP\Gustav\Serializer;

class CatList extends Serializer\Base
{
    public array $cats;
    public int $total;

    public function __construct(array $cats)
    {
        $this->cats = $cats;
        $this->total = count($cats);
    }
}
