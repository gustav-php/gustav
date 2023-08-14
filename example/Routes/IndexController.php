<?php

namespace GustavPHP\Example\Routes;

use GustavPHP\Example\Middlewares\Logs;
use GustavPHP\Gustav\Attribute\{
    Middleware,
    Route
};
use GustavPHP\Gustav\Controller;

#[Middleware(Logs::class)]
class IndexController extends Controller\Base
{
    #[Route('/')]
    public function index()
    {
        return $this->html('<h1>Hello GustavPHP</h1>');
    }
}
