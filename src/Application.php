<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Attribute\Param;
use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\ControllerFactory;
use GustavPHP\Gustav\Middleware;
use GustavPHP\Gustav\Service;
use GustavPHP\Gustav\Router\Method;
use GustavPHP\Gustav\Router\Router;
use HaydenPierce\ClassFinder\ClassFinder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

class Application
{
    /**
     * @var Configuration
     */
    public static Configuration $configuration;
    /**
     * @var ControllerFactory[]
     */
    protected array $controllers = [];
    /**
     * @var array<string,string>
     */
    protected array $files = [];
    /**
     * @var Middleware\Base[]
     */
    protected array $middlewares = [];
    /**
     * @var Service\Base[]
     */
    protected array $services = [];
    public function __construct(
        Configuration $configuration
    ) {
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
        if ($configuration->eventNamespaces) {
            foreach ($configuration->eventNamespaces as $namespace) {
                $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::STANDARD_MODE);
                foreach ($classes as $class) {
                    if (is_subclass_of($class, Event\Base::class)) {
                        Event\Manager::addListener($class);
                    }
                }
            }
        }

        if ($configuration->files) {
            if (\is_dir($configuration->files)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configuration->files));
                foreach ($iterator as $file) {
                    /**
                     * @var SplFileInfo $file
                     */
                    if ($file->isDir()) {
                        continue;
                    }
                    $relative = substr($file->getPathname(), strlen($configuration->files));
                    $this->files[$relative] = $file->getRealPath();
                }
            }
        }
        self::$configuration = $configuration;
    }

    public function addMiddlewares(array $classes): self
    {
        foreach ($classes as $class) {
            $this->middlewares[] = new $class();
        }

        return $this;
    }

    public function addRoutes(array $classes): self
    {
        foreach ($classes as $class) {
            $this->registerRoute($class);
        }

        return $this;
    }

    public function start(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->initialize(...array_map(fn (string $class) => new $class(), $controller->getInjections()));
        }

        $response = self::$configuration->driver::buildResponse();
        $request = self::$configuration->driver::buildRequest();

        try {
            if (array_key_exists($request->getPath(), $this->files)) {
                $path = $this->files[$request->getPath()];
                $response->setBody(file_get_contents($path));
                $response->setStatus(200);
                $response->setHeader('Content-Type', mime_content_type($path));
                return;
            }
            $route = Router::match(Method::fromRequest($request), $request->getPath());
            $controller = $this->controllers[$route->getClass()];
            $controller->setMiddlewares();
            foreach ($controller->getMiddlewares(Middleware\Lifecycle::Before) as $middleware) {
                $middleware->handle($request, $response);
            }
            $params = $route->generateParams($request);
            $instance = $controller->getInstance();
            $payload = $instance->{$route->getFunction()}(...$params);
            if (!$payload instanceof Controller\Response) {
                throw new \Exception('Controller needs to return a Response object');
            }
            foreach ($controller->getMiddlewares(Middleware\Lifecycle::After) as $middleware) {
                $middleware->handle($request, $response);
            }
            $response->importControllerResponse($payload);
        } catch (\Throwable $th) {
            if ($controller ?? null) {
                foreach ($controller->getMiddlewares(Middleware\Lifecycle::Error) as $middleware) {
                    $middleware->handle($request, $response);
                }
            }
            $response = self::$configuration->driver::buildResponse();
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
}
