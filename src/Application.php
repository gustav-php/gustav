<?php

namespace GustavPHP\Gustav;

use Exception;
use GustavPHP\Gustav\Controller\{ControllerFactory, Response};
use GustavPHP\Gustav\Logger\Logger;
use GustavPHP\Gustav\Router\{Method, Router};
use GustavPHP\Gustav\Service\Container;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response as MessageResponse;
use React\Socket\SocketServer;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

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
     * @var null|HttpServer
     */
    protected ?HttpServer $server;
    /**
     * @var null|SocketServer
     */
    protected ?SocketServer $socket;

    /**
     * Creates a new application instance.
     *
     * @param Configuration $configuration
     * @return void
     * @throws Exception
     */
    public function __construct(
        Configuration $configuration
    ) {
        self::$configuration = $configuration;
        foreach (Discovery::discoverController() as $class) {
            $this->addRoutes([$class]);
        }
        foreach (Discovery::discoverSerializers() as $class) {
            Serializer\Manager::addEntity($class);
        }
        foreach (Discovery::discoverEvents() as $class) {
            Event\Manager::addListener($class);
        }
        if ($configuration->files) {
            if (\is_dir($configuration->files)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configuration->files));
                foreach ($iterator as $file) {
                    /**
                     * @var \SplFileInfo $file
                     */
                    if ($file->isDir()) {
                        continue;
                    }
                    $relative = substr($file->getPathname(), strlen($configuration->files));
                    $this->files[$relative] = $file->getRealPath();
                }
            }
        }
    }

    /**
     * Adds middleware classes to the application.
     *
     * @param array<class-string<Middleware\Base>> $classes The classes to add as middleware.
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
     * @param array<class-string<Controller\Base>> $classes The classes to add as routes.
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
     * Checks if the application is running in production mode.
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::$configuration->mode === Mode::Production;
    }

    /**
     * Starts the application.
     *
     * @return void
     */
    public function start(): void
    {
        $this->server = new HttpServer(
            fn (ServerRequestInterface $request, callable $next) => $this->initMiddelware($request, $next),
            fn (ServerRequestInterface $request, callable $next) => $this->customMiddleware($request, $next),
            fn ($request) => $this->handleRequest($request)
        );
        $this->server->on('error', function (\Throwable $th) {
            $error = \json_encode([
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'code' => $th->getCode()
            ]);
            if ($error) {
                Logger::log($error);
            }
        });
        $host = self::$configuration->host;
        $port = self::$configuration->port;
        $socket = new SocketServer("{$host}:{$port}");
        Logger::log("<comment>Gustav PHP Framework</comment>");
        Logger::log('');
        Logger::log("<info>Server running on <href=http://{$host}:{$port}>http://{$host}:{$port}</></info>");
        $this->server->listen($socket);
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
            $routes = $method->getAttributes(Attribute\Route::class);

            foreach ($routes as $route) {
                /**
                 * @var Attribute\Route $instance
                 */
                $instance = $route->newInstance();
                $instance
                    ->setClass($reflector->getName())
                    ->setFunction($method->getName());

                $this->prepareRoute($method, $instance);
                Router::addRoute($instance);
            }
        }
    }

    /**
     * Handles custom middlewares.
     *
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return mixed
     */
    protected function customMiddleware(ServerRequestInterface $request, callable $next): mixed
    {
        /**
         * @var Middleware\Base[] $middlewares
         */
        $middlewares = $request->getAttribute('Gustav-Middlewares', []);
        foreach ($middlewares as $middleware) {
            $request = $middleware->handle($request);
        }
        return $next($request);
    }

    /**
     * Handles a given request.
     *
     * @param ServerRequestInterface $request
     * @return Response|MessageResponse|void
     * @throws InvalidArgumentException
     */
    protected function handleRequest(ServerRequestInterface $request)
    {
        $response = new Response();
        $context = new Context(
            path: $request->getAttribute('Gustav-Path'),
            route: $request->getAttribute('Gustav-Route'),
            controllerFactory: $request->getAttribute('Gustav-Controller')
        );
        try {
            if ($request->getMethod() === 'GET' && array_key_exists($context->path, $this->files)) {
                $path = $this->files[$context->path];
                $contentType = mime_content_type($path);
                return (new Response(
                    status: Response::STATUS_OK,
                    headers: [
                        'Content-Type' => $contentType ?: 'application/octet-stream',
                    ],
                    body: file_get_contents($path)
                ))->build();
            }
            if ($context->route === null || $context->controllerFactory === null) {
                if ($request->getAttribute('Gustav-Exception') !== null) {
                    throw $request->getAttribute('Gustav-Exception');
                } else {
                    throw new \Exception(code: Response::STATUS_INTERNAL_SERVER_ERROR);
                }
            }
            $dependencies = new Container();
            $dependencies->addDependency([ServerRequestInterface::class => fn () => $request]);
            $dependencies->build();
            $instance = $dependencies->make($context->controllerFactory->getClass());
            $payload = $instance->{$context->route->getFunction()}(...$context->route->generateArguments($request));
            if (!$payload instanceof Controller\Response) {
                throw new \Exception('Controller needs to return a Response object');
            }
            $serializer = $payload->getSerializer();
            if ($serializer) {
                $payload->setBody(Serializer\Manager::getEntity($serializer::class)->serialize($serializer));
                $payload->setBody(\json_encode($payload->getBody()));
            }
            return $response->merge($payload)->build();
        } catch (\Throwable $th) {
            if ($th->getCode() === 0) {
                $response->setStatus(Response::STATUS_INTERNAL_SERVER_ERROR);
            } else {
                $response->setStatus($th->getCode());
            }
            $response->setBody([
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'code' => $th->getCode()
            ]);
            return $response->buildJson();
        }
    }

    /**
     * Initializes the application middleware.
     *
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return mixed
     */
    protected function initMiddelware(ServerRequestInterface $request, callable $next): mixed
    {
        try {
            $path = ltrim($request->getUri()->getPath(), '/');
            $request = $request->withAttribute('Gustav-Path', $path);
            $route = Router::match(Method::fromRequest($request), $path);
            $controller = $this->controllers[$route->getClass()];
            $request = $request
                ->withAttribute('Gustav-Route', $route)
                ->withAttribute('Gustav-Controller', $controller)
                ->withAttribute('Gustav-Middlewares', $controller->getMiddlewares());
        } catch (\Throwable $th) {
            $request = $request
                ->withAttribute('Gustav-Route', null)
                ->withAttribute('Gustav-Exception', $th);
        }

        return $next($request);
    }

    /**
     * Registers a route in the application.
     *
     * @param class-string<Controller\Base> $class The class to register as a route.
     * @return void
     */
    protected function registerRoute(string $class): void
    {
        $controller = new ControllerFactory($class);
        $reflector = new ReflectionClass($class);
        $this->addMethods($reflector);
        $this->controllers[$class] = $controller;
    }

    /**
     * Adds parameters from a given reflection method to a route.
     *
     * @param ReflectionMethod $method The reflection method to add parameters from.
     * @param Attribute\Route $route The route to add parameters to.
     * @return void
     */
    private function prepareRoute(ReflectionMethod $method, Attribute\Route $route): void
    {
        foreach ($method->getParameters() as $parameter) {
            $param = $parameter->getAttributes(Attribute\Param::class)[0] ?? null;
            if ($param) {
                /** @var Attribute\Param $instance */
                $instance = $param->newInstance();
                $instance->setParameter($parameter->getName());
                $route->addArgument($parameter->getName(), $instance);
                continue;
            }
            $body = $parameter->getAttributes(Attribute\Body::class)[0] ?? null;
            if ($body) {
                /** @var Attribute\Body $instance */
                $instance = $body->newInstance();
                $instance->setRequired(!$parameter->isOptional());
                $route->addArgument($parameter->getName(), $instance);
                continue;
            }
            $query = $parameter->getAttributes(Attribute\Query::class)[0] ?? null;
            if ($query) {
                /** @var Attribute\Query $instance */
                $instance = $query->newInstance();
                $instance->setRequired(!$parameter->isOptional());
                if (DTO\Mapper::isParameterDTO($parameter)) {
                    /**
                     * @var ReflectionNamedType $type
                     */
                    $type = $parameter->getType();
                    $instance->setDTO(DTO\Mapper::fromReflection($type));
                }
                $route->addArgument($parameter->getName(), $instance);
                continue;
            }
            throw new \Exception('Invalid parameter type');
        }
    }
}
