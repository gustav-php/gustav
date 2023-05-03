<?php

namespace TorstenDittmann\Gustav;

use HaydenPierce\ClassFinder\ClassFinder;
use ReflectionClass;
use ReflectionMethod;
use TorstenDittmann\Gustav\Attribute\Param;
use TorstenDittmann\Gustav\Attribute\Route;
use TorstenDittmann\Gustav\Controller\ControllerFactory;
use TorstenDittmann\Gustav\Router\Method;
use TorstenDittmann\Gustav\Router\Router;

class Application
{
    public function __construct(
        protected ?Configuration $configuration = null,
        array $routes = []
    ) {
        $this->addRoutes($routes);
        if ($configuration) {
            if ($configuration->routeNamespaces) {
                foreach ($configuration->routeNamespaces as $namespace) {
                    $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::STANDARD_MODE);
                    foreach ($classes as $class) {
                        if (is_subclass_of($class, Controller\Base::class)) {
                            $this->addRoutes([$class]);
                        }
                    }
                }
            }
        }
    }

    /**
     * @var \TorstenDittmann\Gustav\Controller\ControllerFactory[]
     */
    protected array $controllers = [];
    /**
     * @var \TorstenDittmann\Gustav\Service\Base[]
     */
    protected array $services = [];
    /**
     * @var \TorstenDittmann\Gustav\Middleware\Base[]
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
        $controller = new ControllerFactory($class);
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();
        if ($constructor !== null) {
            $controller->setInjections($constructor);
        }
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

    public function start(): void
    {
        $this->configuration ??= new Configuration();

        foreach ($this->controllers as $controller) {
            $controller->initialize(...array_map(fn (string $class) => new $class(), $controller->getInjections()));
        }

        $context = new $this->configuration->context();
        $response = $this->configuration->driver::buildResponse();
        $request = $this->configuration->driver::buildRequest();

        try {
            $route = Router::match(Method::fromRequest($request), $request->getPath());
            /**
             * @var ControllerFactory $controller
             */
            $controller = $this->controllers[$route->getClass()];
            $controller->setMiddlewares();
            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->handle($request, $response, $context);
            }
            $controller->setContext($context);
            $params = $route->generateParams($request);
            $instance = $controller->getInstance();
            $payload = $instance->{$route->getFunction()}(...$params);
            if ($payload instanceof $response) {
                $response = $payload;
            } else {
                $body = \json_encode($payload);
                $response->setHeader('Content-Type', 'application/json');
                $response->setStatus(200);
                $response->setBody($body);
            }
        } catch (\Throwable $th) {
            $response = $this->configuration->driver::buildResponse();
            $body = \json_encode([
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'trace' => $th->getTrace(),
            ]);
            $response->setHeader('Content-Type', 'application/json');
            $response->setStatus(500);
            $response->setBody($body);
        } finally {
            $response->setHeader('Content-Type', 'application/json');
            $response->send();
        }
    }
}
