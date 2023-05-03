<?php

namespace GustavPHP\Example\Services;

use GustavPHP\Gustav\Service;

class CatsService extends Service\Base
{
    protected array $database = [
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
    ];

    public function list(): array
    {
        return $this->database;
    }

    public function get(string $id): array|null
    {
        foreach ($this->database as $cat) {
            if ($cat['id'] === $id) return $cat;
        }

        return null;
    }

    public function create(string $name): array
    {
        $cat = [
            'id' => (string) count($this->database) + 1,
            'name' => $name
        ];

        $this->database[] = $cat;

        return $cat;
    }
}
