<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TorstenDittmann\Gustav\Application;
use TorstenDittmann\Gustav\Method;
use TorstenDittmann\Gustav\Attributes\Param;
use TorstenDittmann\Gustav\Attributes\Route;

class CatsController
{
    protected array $cats = [
        [
            'name' => 'lili'
        ],
        [
            'name' => 'kitty'
        ],
        [
            'name' => 'nala'
        ],
    ];

    #[Route('/cats')]
    public function list()
    {
        return $this->cats;
    }

    #[Route('/cats', Method::POST)]
    public function create(
        #[Param('name')] string $name
    ) {
        $cat = [
            'name' => $name
        ];

        $this->cats[] = $cat;

        return $cat;
    }

    #[Route('/cats/:cat')]
    public function get(
        #[Param('cat')] string $name
    ) {
        foreach ($this->cats as $cat) {
            if ($cat['name'] === $name) return $cat;
        }

        throw new Exception('Cat not found.', 404);
    }

    #[Route('/cats/:cat', Method::PATCH)]
    public function update(
        #[Param('cat')] string $name,
        #[Param('name')] string $newName
    ) {
        foreach ($this->cats as $i => $cat) {
            if ($cat['name'] === $name) {
                $this->cats[$i]['name'] = $newName;
                return;
            }
        }

        throw new Exception('Cat not found.', 404);
    }

    #[Route('/cats/:cat', Method::DELETE)]
    public function delete(
        #[Param('cat')] string $name
    ) {
        $this->cats = array_filter($this->cats, fn (array $cat) => $cat['name'] !== $name);
    }
}

$app = new Application();
$app->register(CatsController::class);

$app->start();
