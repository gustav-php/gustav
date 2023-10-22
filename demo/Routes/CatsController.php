<?php

namespace GustavPHP\Demo\Routes;

use DI\Attribute\Inject;
use Exception;
use GustavPHP\Demo\Middlewares\Logs;
use GustavPHP\Demo\Services\CatsService;
use GustavPHP\Gustav\Attribute\{
    Middleware,
    Route,
    Param
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;

#[Middleware(Logs::class)]
class CatsController extends Controller\Base
{
    #[Inject]
    protected CatsService $catsService;

    #[Route('/cats')]
    public function list()
    {
        return $this->serialize($this->catsService->list());
    }

    #[Route('/cats', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        return $this->serialize($this->catsService->create($name));
    }

    #[Route('/cats/:id')]
    public function get(
        #[Param('id')] string $id
    ) {
        $cat = $this->catsService->get($id);
        if (!$cat) {
            throw new Exception('Cat not found.', 404);
        }
        return $this->serialize($cat);
    }
}
