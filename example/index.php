<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use TorstenDittmann\Gustav\Application;
use TorstenDittmann\Gustav\Attribute;
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Attribute\Route;
use TorstenDittmann\Gustav\Context;
use TorstenDittmann\Gustav\Controller;
use TorstenDittmann\Gustav\Middleware;
use TorstenDittmann\Gustav\Router\Method;
use TorstenDittmann\Gustav\Service;

class Dogs extends Service\Base
{
    public string $name = 'wuff';
}
class Police extends Middleware\Base
{

    public function __construct()
    {
    }

    public function handle(Request $request, Response $response, Context $context): void
    {
        var_dump("asd");
    }
}

#[Attribute\Middleware(Police::class)]
class CatsController extends Controller\Base
{
    protected array $cats = [
        [
            'id' => '1',
            'name' => 'lili'
        ],
        [
            'id' => '2',
            'name' => 'kitty'
        ],
        [
            'id' => '3',
            'name' => 'nala'
        ],
    ];

    public function __construct(protected Dogs $dogs)
    {
    }

    #[Route('/')]
    public function index()
    {
        return true;
    }

    #[Route('/dogs/:name/:age')]
    public function params(#[Param('name')] string $name, #[Param('age')] int $age)
    {
        return [
            'name' => $name,
            'age' => $age
        ];
    }

    #[Route('/cats')]
    public function list()
    {
        return $this->cats;
    }

    #[Route('/cats', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        $cat = [
            'name' => $name
        ];

        $this->cats[] = $cat;

        return $cat;
    }

    #[Route('/cats/:cat')]
    public function get(
        #[Param('cat')] string $id
    ) {
        foreach ($this->cats as $cat) {
            if ($cat['id'] === $id) return $cat;
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

$app = new Application(routes: [CatsController::class]);

$app->start();
