<?php

namespace GustavPHP\Example\Routes;

use Exception;
use GustavPHP\Example\Middlewares\Logs;
use GustavPHP\Example\Services\CatsService;
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
    public function __construct(protected CatsService $catsService)
    {
    }

    #[Route('/cats')]
    public function list()
    {
        return $this->json($this->catsService->list());
    }

    #[Route('/cats', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        return $this->json($this->catsService->create($name));
    }

    #[Route('/cats/:id')]
    public function get(
        #[Param('id')] string $id
    ) {
        return $this->json($this->catsService->get($id) ?? throw new Exception('Cat not found.', 404));
    }
}
