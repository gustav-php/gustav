<?php

namespace GustavPHP\Example\Routes;

use GustavPHP\Example\Middlewares\Logs;
use GustavPHP\Gustav\Attribute\{
    Middleware,
    Route
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Event\Manager;

#[Middleware(Logs::class)]
class IndexController extends Controller\Base
{
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
