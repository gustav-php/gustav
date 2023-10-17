<?php

namespace GustavPHP\Example\Services;

use DI\Attribute\Inject;
use GustavPHP\Gustav\Service;

class DogsService extends Service\Base
{
    #[Inject]
    protected DataService $dataService;

    public function create(string $name): array
    {
        $cat = [
            'id' => (string) count($this->dataService->cats) + 1,
            'name' => $name
        ];

        $this->dataService->cats[] = $cat;

        return $cat;
    }

    public function get(string $id): array|null
    {
        foreach ($this->dataService->cats as $cat) {
            if ($cat['id'] === $id) {
                return $cat;
            }
        }

        return null;
    }

    public function list(): array
    {
        return $this->dataService->cats;
    }
}
