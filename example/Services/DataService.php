<?php

namespace GustavPHP\Example\Services;

use GustavPHP\Gustav\Service;

class DataService extends Service\Base
{
    public array $cats = [
        [
            'id' => '1',
            'name' => 'lili'
        ],
        [
            'id' => '2',
            'name' => 'kitty'
        ],
        [
            'id' => '3',
            'name' => 'nala'
        ],
        [
            'id' => 'a',
            'name' => 'nala'
        ],
    ];
}
