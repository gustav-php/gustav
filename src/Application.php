<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Attribute\Param;
use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller\ControllerFactory;
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

    /**
     * Adds middleware classes to the application.
     *
     * @param array $classes The classes to add as middleware.
     * @return self Returns the application instance.
     */
    public function addMiddlewares(array $classes): self
    {
        foreach ($classes as $class) {
            $this->middlewares[] = new $class();
        }

        return $this;
    }

    /**
     * Adds route classes to the application.
     *
     * @param array<string> $classes The classes to add as routes.
     * @return self Returns the application instance.
     */
    public function addRoutes(array $classes): self
    {
        foreach ($classes as $class) {
            $this->registerRoute($class);
        }

        return $this;
    }

    /**
     * Starts the application.
     *
     * @return void
     */
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

    /**
     * Adds methods from a given reflection class to the application.
     *
     * @param ReflectionClass $reflector The reflection class to add methods from.
     * @return void
     */
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

    /**
     * Adds parameters from a given reflection method to a route.
     *
     * @param ReflectionMethod $method The reflection method to add parameters from.
     * @param Route $route The route to add parameters to.
     * @return void
     */
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

    /**
     * Registers a route in the application.
     *
     * @param string $class The class to register as a route.
     * @return void
     */
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
