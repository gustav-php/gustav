<?php

namespace GustavPHP\Gustav;

use Composer\InstalledVersions;
use Exception;
use GustavPHP\Gustav\Controller\{ControllerFactory, Response};
use GustavPHP\Gustav\Http\Binding\RequestBinder;
use GustavPHP\Gustav\Http\CallableRequestHandler;
use GustavPHP\Gustav\Http\Exception\{HttpException, RequestInputException, ValidationException};
use GustavPHP\Gustav\Middleware\Pipeline;
use GustavPHP\Gustav\Router\{Method, Router};
use GustavPHP\Gustav\Service\Container;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;
use SplFileInfo;
use stdClass;
use Throwable;

class Application implements RequestHandlerInterface
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
     * @var array<MiddlewareInterface>
     */
    protected array $middlewares = [];
    /**
     * @var array<int,RequestBinder>
     */
    protected array $requestBinders = [];

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
        Router::reset();
        Serializer\Manager::reset();
        Event\Manager::reset();
        View::reset();

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
            if (is_dir($configuration->files)) {
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
    }

    /**
     * Add application-wide middleware.
     */
    public function addMiddleware(MiddlewareInterface ...$middlewares): self
    {
        array_push($this->middlewares, ...$middlewares);

        return $this;
    }

    /**
     * Adds route classes to the application.
     *
     * @param array<class-string<Controller\Base>> $classes The classes to add as routes.
     * @return self Returns the application instance.
     * @throws ReflectionException
     */
    public function addRoutes(array $classes): self
    {
        foreach ($classes as $class) {
            $this->registerRoute($class);
        }

        return $this;
    }

    /**
     * Handle one PSR-7 request independently of the server transport.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $path = ltrim($request->getUri()->getPath(), '/');
            $request = $request->withAttribute('Gustav-Path', $path);

            return (new Pipeline(
                $this->middlewares,
                new CallableRequestHandler($this->handleRoutedRequestSafely(...)),
            ))->handle($request);
        } catch (Throwable $th) {
            return $this->renderException($th);
        }
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
        $worker = Worker::create();
        $factory = new Psr17Factory();
        $psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

        while (true) {
            try {
                $request = $psr7->waitRequest();
                if ($request === null) {
                    break;
                }
            } catch (Throwable $e) {
                // Although the PSR-17 specification clearly states that there can be
                // no exceptions when creating a request, however, some implementations
                // may violate this rule. Therefore, it is recommended to process the
                // incoming request for errors.
                //
                // Send "Bad Request" response.
                $psr7->respond(new Psr7Response(400));
                continue;
            }

            try {
                $psr7->respond($this->handle($request));
            } catch (Throwable $e) {
                $psr7->getWorker()->error((string) $e);
            }
        }
    }

    /**
     * Adds methods from a given reflection class to the application.
     *
     * @param ReflectionClass $reflector The reflection class to add methods from.
     * @return void
     * @throws Exception
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
     * Invoke the matched controller.
     */
    protected function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getAttribute('Gustav-Route');
        $controller = $request->getAttribute('Gustav-Controller');
        if (!$route instanceof Attribute\Route || !$controller instanceof ControllerFactory) {
            throw new LogicException('Request route has not been initialized');
        }

        $dependencies = new Container();
        $dependencies->addDependency([ServerRequestInterface::class => fn () => $request]);
        $dependencies->build();
        $instance = $dependencies->make($controller->getClass());
        $requestBinder = $this->requestBinders[spl_object_id($route)] ?? null;
        if ($requestBinder === null) {
            throw new LogicException('Request binder has not been initialized');
        }
        $payload = $instance->{$route->getFunction()}(...$requestBinder->bind($request, $route->getPlaceholders()));

        if ($payload instanceof Controller\Response) {
            $serializer = $payload->getSerializer();
            if ($serializer) {
                $payload->setBody(Serializer\Manager::getEntity($serializer::class)->serialize($serializer));
                $payload->setBody(json_encode($payload->getBody()));
            }

            return $payload->build();
        }
        if ($payload instanceof ResponseInterface) {
            return $payload;
        }

        throw new LogicException('Controller needs to return a response object');
    }

    /**
     * Fetches the relevant code snippet from the given file and line.
     *
     * @param string $file The file to fetch the code snippet from.
     * @param int $line The line to fetch the code snippet from.
     * @return string Returns the code snippet.
     */
    protected function getCodeBlockFromTrace(string $file, int $line): string
    {
        if ($line < 1 || !is_readable($file)) {
            return '';
        }

        $lines = file($file);
        if ($lines === false) {
            return '';
        }

        return implode('', array_slice($lines, max(0, $line - 5), 9));
    }

    /**
     * Resolve a route and run its controller and method middleware.
     */
    protected function handleRoutedRequest(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getAttribute('Gustav-Path');
        if (!is_string($path)) {
            throw new LogicException('Request path has not been initialized');
        }

        if ($request->getMethod() === Method::GET->value && array_key_exists($path, $this->files)) {
            return $this->serveStaticFile($this->files[$path]);
        }

        $route = Router::match(Method::fromRequest($request), $path);
        $controller = $this->controllers[$route->getClass()] ?? null;
        if ($controller === null) {
            throw new LogicException("Controller '{$route->getClass()}' has not been registered");
        }

        $middlewares = $controller->getMiddlewares($route->getFunction());
        $request = $request
            ->withAttribute('Gustav-Route', $route)
            ->withAttribute('Gustav-Controller', $controller)
            ->withAttribute('Gustav-Middlewares', $middlewares);

        return (new Pipeline(
            $middlewares,
            new CallableRequestHandler($this->dispatchRequest(...)),
        ))->handle($request);
    }

    /**
     * Convert route and controller exceptions inside the application middleware
     * pipeline so outer middleware can inspect the resulting error response.
     */
    protected function handleRoutedRequestSafely(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->handleRoutedRequest($request);
        } catch (Throwable $throwable) {
            return $this->renderException($throwable);
        }
    }

    /**
     * @param Throwable $th
     * @return array<stdClass>
     */
    protected function prepareTrace(Throwable $th): array
    {
        return array_map(function ($trace) {
            $object = new stdClass();
            $object->file = $trace['file'] ?? null;
            $object->line = $trace['line'] ?? null;
            $object->function = $trace['function'];
            $object->type = $trace['type'] ?? null;
            $object->class = $trace['class'] ?? null;
            $object->snippet = '';
            if ($object->file !== null && $object->line !== null) {
                $object->snippet = $this->getCodeBlockFromTrace($object->file, $object->line);
            }

            return $object;
        }, $th->getTrace());
    }


    /**
     * Registers a route in the application.
     *
     * @param class-string<Controller\Base> $class The class to register as a route.
     * @return void
     * @throws ReflectionException
     */
    protected function registerRoute(string $class): void
    {
        $controller = new ControllerFactory($class);
        $reflector = new ReflectionClass($class);
        $this->addMethods($reflector);
        $this->controllers[$class] = $controller;
    }

    protected function renderException(Throwable $throwable): ResponseInterface
    {
        $status = $throwable instanceof HttpException
            ? $throwable->getStatusCode()
            : 500;

        $headers = $throwable instanceof HttpException
            ? $throwable->getHeaders()
            : [];

        if ($throwable instanceof RequestInputException) {
            $error = [
                'status' => $status,
                'message' => $throwable->getMessage(),
            ];
            if ($throwable instanceof ValidationException) {
                $error['violations'] = array_map(
                    fn ($violation): array => $violation->toArray(),
                    $throwable->getViolations(),
                );
            }

            return new Psr7Response(
                $status,
                array_merge(['Content-Type' => 'application/json'], $headers),
                (string) json_encode(['error' => $error], JSON_INVALID_UTF8_SUBSTITUTE),
            );
        }

        if (self::isProduction()) {
            $message = $status >= 500
                ? 'Server Error'
                : ($throwable->getMessage() ?: 'Request failed');

            return new Psr7Response(
                $status,
                array_merge(['Content-Type' => 'application/json'], $headers),
                (string) json_encode(
                    [
                        'error' => [
                            'status' => $status,
                            'message' => $message,
                        ],
                    ],
                    JSON_INVALID_UTF8_SUBSTITUTE,
                ),
            );
        }

        try {
            return (new Response(
                body: View::render(__DIR__ . '/../views/exception.latte', [
                    'title' => get_class($throwable),
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'code' => $status,
                    'trace' => $this->prepareTrace($throwable),
                    'snippet' => $this->getCodeBlockFromTrace($throwable->getFile(), $throwable->getLine()),
                    'version' => InstalledVersions::getPrettyVersion('gustav-php/gustav'),
                ]),
                status: $status,
                headers: $headers,
            ))->buildHtml();
        } catch (Throwable) {
            return new Psr7Response(
                500,
                ['Content-Type' => 'text/plain'],
                'Server Error',
            );
        }
    }

    protected function serveStaticFile(string $path): ResponseInterface
    {
        $body = file_get_contents($path);
        if ($body === false) {
            throw new HttpException(500, 'Unable to read static file');
        }

        return new Psr7Response(
            200,
            ['Content-Type' => mime_content_type($path) ?: 'application/octet-stream'],
            $body,
        );
    }

    /**
     * Adds parameters from a given reflection method to a route.
     *
     * @param ReflectionMethod $method The reflection method to add parameters from.
     * @param Attribute\Route $route The route to add parameters to.
     * @return void
     * @throws Exception
     */
    private function prepareRoute(ReflectionMethod $method, Attribute\Route $route): void
    {
        $this->requestBinders[spl_object_id($route)] = RequestBinder::compile($method, $route->getPath());
    }
}
