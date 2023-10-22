<?php

namespace GustavPHP\Demo\Routes;

use DI\Attribute\Inject;
use GustavPHP\Demo\Middlewares\Logs;
use GustavPHP\Demo\Services\DataService;
use GustavPHP\Gustav\Attribute\{
    Middleware,
    Route
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Event\Manager;

#[Middleware(Logs::class)]
class IndexController extends Controller\Base
{
    #[Inject]
    protected DataService $dataService;

    #[Route('/')]
    public function index()
    {
        Manager::dispatch('test', [
            'test' => 'test'
        ]);
        return $this->view(__DIR__ . '/../views/index.latte', [
            'test' => 'lorem ipsum'
        ]);
    }
}