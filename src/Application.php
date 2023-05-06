<?php

namespace GustavPHP\Gustav;

use HaydenPierce\ClassFinder\ClassFinder;
use ReflectionClass;
use ReflectionMethod;
use GustavPHP\Gustav\Attribute\Param;
use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\ControllerFactory;
use GustavPHP\Gustav\Logger\Logger;
use GustavPHP\Gustav\Middleware\Lifecycle;
use GustavPHP\Gustav\Router\Method;
use GustavPHP\Gustav\Router\Router;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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

            if ($configuration->files) {
                if (\is_dir($configuration->files)) {
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configuration->files));
                    foreach ($iterator as $file) {
                        /** @var SplFileInfo $file */
                        if ($file->isDir()) {
                            continue;
                        }
                        $relative = substr($file->getPathname(), strlen($configuration->files));
                        $this->files[$relative] = $file->getRealPath();
                    }
                }
            }
        }
    }

    /**
     * @var \GustavPHP\Gustav\Controller\ControllerFactory[]
     */
    protected array $controllers = [];
    /**
     * @var \GustavPHP\Gustav\Service\Base[]
     */
    protected array $services = [];
    /**
     * @var \GustavPHP\Gustav\Middleware\Base[]
     */
    protected array $middlewares = [];
    /**
     * @var array<string,string>
     */
    protected array $files = [];

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
            if (array_key_exists($request->getPath(), $this->files)) {
                $path = $this->files[$request->getPath()];
                $response->setBody(file_get_contents($path));
                $response->setStatus(200);
                $response->setHeader('Content-Type', mime_content_type($path));
                return;
            }
            $route = Router::match(Method::fromRequest($request), $request->getPath());
            /** @var ControllerFactory $controller */
            $controller = $this->controllers[$route->getClass()];
            $controller->setMiddlewares();
            foreach ($controller->getMiddlewares(Lifecycle::Before) as $middleware) {
                $middleware->handle($request, $response, $context);
            }
            $controller->setContext($context);
            $params = $route->generateParams($request);
            $instance = $controller->getInstance();
            $payload = $instance->{$route->getFunction()}(...$params);
            foreach ($controller->getMiddlewares(Lifecycle::After) as $middleware) {
                $middleware->handle($request, $response, $context);
            }
            if ($payload instanceof $response) {
                $response = $payload;
            } else {
                $body = \json_encode($payload);
                $response->setHeader('Content-Type', 'application/json');
                $response->setStatus(200);
                $response->setBody($body);
            }
        } catch (\Throwable $th) {
            foreach ($controller->getMiddlewares(Lifecycle::Error) as $middleware) {
                $middleware->handle($request, $response, $context);
            }
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
            $response->send();
        }
    }
}
