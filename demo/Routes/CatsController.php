<?php

namespace GustavPHP\Demo\Routes;

use DI\Attribute\Inject;
use Exception;
use GustavPHP\Demo\Middlewares\Logs;
use GustavPHP\Demo\Services\CatsService;
use GustavPHP\Gustav\Attribute\{
    Middleware,
    Param,
    Route
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;

#[Middleware(new Logs())]
class CatsController extends Controller\Base
{
    #[Inject]
    protected CatsService $catsService;

    #[Route('/cats', Method::POST)]
    public function create(#[Param('name')] string $name): Controller\Response
    {
        return $this->serialize($this->catsService->create($name));
    }

    #[Route('/cats/:id')]
    public function get(
        #[Param('id')] string $id
    ): Controller\Response {
        $cat = $this->catsService->get($id);
        if (!$cat) {
            throw new Exception('Cat not found.', 404);
        }
        return $this->serialize($cat);
    }

    #[Route('/cats')]
    public function list(): Controller\Response
    {
        return $this->serialize($this->catsService->list());
    }
}
