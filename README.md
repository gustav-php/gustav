# Gustav - PHP Framework

Gustav is a PHP framework for building web applications. It is designed to be simple, object-oriented and using the latest features of PHP.

## Controllers

Controllers are the heart of the framework. They are responsible for handling incoming requests and returning a response. All controllers must extend the `Controller\Base` class.

```php
use TorstenDittmann\Gustav\Controller;

class DogsController extends Controller\Base
{
}
```

Routes can be defined by attaching the Route Attributes to public controller functions.

```php
//...
use TorstenDittmann\Gustav\Attribute\Route;

class DogsController extends Controller\Base
{
    protected array $dogs = [
        'Fido',
        'Rex',
        'Spot',
    ];

    #[Route('/dogs')]
    public function list()
    {
        return $this->dogs;
    }
}
```

You can also define the HTTP method that the route should respond to adding the `Method` enum to the `Route` parameters. You can also define the route parameters by adding the `Param` attribute to the function parameters.

The `name` argument in the `Param` attribute is used to define the parameter mapped to the payload.

```php
//...
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Router\Method;

class DogsController extends Controller\Base
{
    //...
    #[Route('/dogs', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        $this->dogs[] = $name;

        return $this->dogs;
    }
}
```

Now we just need to inizialize the framework and we are ready to go.

```php
use TorstenDittmann\Gustav\Application;

//...

$app = new Application(routes: [CatsController::class]);

$app->start();
```

Full example:

```php
use TorstenDittmann\Gustav\Application;
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Attribute\Route;
use TorstenDittmann\Gustav\Controller;
use TorstenDittmann\Gustav\Router\Method;

class DogsController extends Controller\Base
{
    protected array $dogs = [
        'Fido',
        'Rex',
        'Spot',
    ];

    #[Route('/dogs')]
    public function list()
    {
        return $this->dogs;
    }

    #[Route('/dogs', Method::POST)]
    public function create(#[Param('name')] string $name)
    {
        $this->dogs[] = $name;

        return $this->dogs;
    }
}

$app = new Application(routes: [CatsController::class]);

$app->start();
```

## Routing

The framework uses an internal router to match incoming requests to the defined routes. The router is parsing all routes and stores them in a hashmap. This way the router can match the incoming request to the correct route in O(1) time.

```php
#[Route('/dogs')]
public function list()

#[Route('/dogs', Method::POST)]
public function create(#[Param('name')] string $name)

#[Route('/dogs/:dog')]
public function get(#[Param('dog')] string $id)

#[Route('/dogs/:dog/collars', Method::POST)]
public function get(#[Param('dog')] string $id, #[Param('color')] string $color)

#[Route('/dogs/:dog/collars/:collar')]
public function get(#[Param('dog')] string $id, #[Param('collar')] string $collar)
```

## Services

Services are classes that can be injected into controllers. They are defined by extending the `Service\Base` class.

```php
use TorstenDittmann\Gustav\Service;

class Police extends Service\Base
{
    public string $icon = '👮‍♀️';
}
```

To inject a service into a controller, you need to add the `Service` attribute to the controllers constructor.

```php
class DogsController extends Controller\Base
{
    public function __construct(protected Police $police)
    {
    }

    #[Route('/police')]
    public function police()
    {
        return $this->police->icon;
    }
}
```

## Middlewares

Middlewares are classes that are run before the controller is executed. They are defined by extending the `Middleware\Base` class.

```php
class Security extends Middleware\Base
{

    public function __construct()
    {
    }

    public function handle(Request $request, Response $response, Context $context): void
    {
        // do stuff here
    }
}
```

To add a middleware to a controller, you need to add the `Middleware` attribute to the controllers class.

```php
use TorstenDittmann\Gustav\Attribute;

#[Attribute\Middleware(Security::class)]
class CatsController extends Controller\Base
//...
```

You can extend the `Context` class to add custom data to the context. The Context is passed to all following middlewares and the executed controller.

```php
class DogsController extends Controller\Base
{
    #[Route('/from-context')]
    public function police()
    {
        return $this->context;
    }
}
```
