<?php

namespace TorstenDittmann\Gustav;

use ReflectionClass;
use ReflectionMethod;
use Sabre\HTTP\Response;
use Sabre\HTTP\Sapi;
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Attribute\Route;
use TorstenDittmann\Gustav\Controller\Base;
use TorstenDittmann\Gustav\Router\Method;
use TorstenDittmann\Gustav\Router\Router;

class Application
{
    public function __construct(
        protected ?Configuration $configuration = null,
        array $routes = []
    ) {
        $this->addRoutes($routes);
    }

    /**
     * @var Controller[]
     */
    protected array $controllers = [];
    /**
     * @var Service[]
     */
    protected array $services = [];
    /**
     * @var Middleware[]
     */
    protected array $middlewares = [];

    public function addRoutes(array $classes): self
    {
        foreach ($classes as $class) {
            $this->registerRoute($class);
        }

        return $this;
    }

    protected function registerRoute(string $class): void
    {
        $controller = new Base($class);
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();
        $controller->setInjections($constructor);
        $this->addMethods($reflector);
        $this->controllers[$class] = $controller;
    }

    public function addMiddlewares(array $classes): self
    {
        foreach ($classes as $class) {
            $this->middlewares[] = new $class();
        }

        return $this;
    }


    protected function addMethods(ReflectionClass $reflector): void
    {
        foreach ($reflector->getMethods() as $method) {
            $routes = $method->getAttributes(Route::class);

            foreach ($routes as $route) {
                /**
                 * @var Route $instance
                 */
                $instance = $route->newInstance();
                $instance
                    ->setClass($reflector->getName())
                    ->setFunction($method->getName());

                $this->addParameters($method, $instance);
                Router::addRoute($instance);
            }
        }
    }

    protected function addParameters(ReflectionMethod $method, Route $route): void
    {
        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Param::class) as $attribute) {
                /** @var Param $instance */
                $instance = $attribute->newInstance();
                $instance
                    ->setParameter($parameter->getName())
                    ->setRequired(!$parameter->isOptional());
                $route->addParam($instance->getParameter(), $instance);
            }
        }
    }

    public function start()
    {
        $this->configuration ??= new Configuration();

        foreach ($this->services as $class => $service) {
            $service->initialize($class);
        }
        foreach ($this->controllers as $controller) {
            $controller->initialize(...array_map(fn (string $class) => new $class(), $controller->getInjections()));
        }

        $context = new Context();
        $response = new Response();
        $request = Sapi::getRequest();

        try {
            $route = Router::match(Method::fromRequest($request), $request->getPath());
            /**
             * @var Controller\Base $controller
             */
            $controller = $this->controllers[$route->getClass()]->getInstance();
            $controller->setMiddlewares();
            foreach ($this->middlewares as $middleware) {
                $middleware->handle($request, $response, $context);
            }
            $controller->setContext($context);
            $params = $route->generateParams($request);
            $payload = $controller->{$route->getFunction()}(...$params);
            if ($payload instanceof Response) {
                $response = $payload;
            } else {
                $body = \json_encode($payload);
                $response->setStatus(200);
                $response->setBody($body);
            }
        } catch (\Throwable $th) {
            $response = new Response();
            $body = \json_encode([
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'trace' => $th->getTrace(),
            ]);
            $response->setStatus(500);
            $response->setBody($body);
        } finally {
            $response->setHeader('Content-Type', 'application/json');
            Sapi::sendResponse($response);
        }
    }
}
