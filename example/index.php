<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Sabre\HTTP\Sapi;
use TorstenDittmann\Gustav\Application;
use TorstenDittmann\Gustav\Router;
use TorstenDittmann\Gustav\Attributes\Param;
use TorstenDittmann\Gustav\Attributes\Route;

/*class CatsController
{
    protected array $cats = [
        '1' => 'mimi',
        '2' => 'kitty',
        '3' => 'nala',
    ];

    #[Route('/cats')]
    public function read(
        #[Param('cat')] string $id
    ) {
        return $this->cats[$id] ?? throw new Exception('Cat not found.', 404);
    }

    #[Route('/cats', Route::POST)]
    public function create(
        #[Param('name')] string $name
    )
    {
        return ['test' => $id];
    }
}

$app = new Application();
$app->register(CatsController::class);

$app->start();*/

$request = Sapi::getRequest();

Router::addRoute('/', 'index');
Router::addRoute('/about', 'about');
Router::addRoute('/blog', 'blog');
Router::addRoute('/blog/author', 'blog_author');
Router::addRoute('/blog/:post', 'blog_post');
var_dump('found', Router::match($request->getPath()));
