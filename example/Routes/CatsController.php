<?php

namespace GustavPHP\Example\Routes;

use Exception;
use GustavPHP\Example\Middlewares\Logger;
use GustavPHP\Example\Services\CatsService;
use GustavPHP\Gustav\Attribute;
use GustavPHP\Gustav\Attribute\Param;
use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;

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
