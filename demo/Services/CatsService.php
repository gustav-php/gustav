<?php

namespace GustavPHP\Demo\Services;

use DI\Attribute\Inject;
use GustavPHP\Demo\Serializers\Cat;
use GustavPHP\Demo\Serializers\CatList;
use GustavPHP\Gustav\Service;

class CatsService extends Service\Base
{
    #[Inject]
    public DataService $dataService;

    public function list(): CatList
    {
        return new CatList(array_map(fn ($cat) => new Cat($cat['id'], $cat['name']), $this->dataService->cats));
    }

    public function get(string $id): Cat|null
    {
        foreach ($this->dataService->cats as $cat) {
            if ($cat['id'] === $id) return new Cat($cat['id'], $cat['name']);
        }

        return null;
    }

    public function create(string $name): Cat
    {
        $cat = [
            'id' => (string) count($this->dataService->cats) + 1,
            'name' => $name
        ];

        $this->dataService->cats[] = $cat;

        return new Cat($cat['id'], $cat['name']);
    }
}
