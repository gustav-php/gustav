<?php

namespace TorstenDittmann\Example\Routes;

use Exception;
use TorstenDittmann\Example\Middlewares\Logger;
use TorstenDittmann\Example\Services\CatsService;
use TorstenDittmann\Gustav\Attribute;
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Attribute\Route;
use TorstenDittmann\Gustav\Controller;
use TorstenDittmann\Gustav\Router\Method;

#[Attribute\Middleware(Logger::class)]
class CatsController extends Controller\Base
{
    public function __construct(protected CatsService $catsService)
    {
    }

    #[Route('/cats')]
    public function list()
    {
        return $this->catsService->list();
    }

    #[Route('/cats', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        return $this->catsService->create($name);
    }

    #[Route('/cats/:id')]
    public function get(
        #[Param('id')] string $id
    ) {
        return $this->catsService->get($id) ?? throw new Exception('Cat not found.', 404);
    }
}
