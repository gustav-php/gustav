<?php

namespace GustavPHP\Gustav;

use Composer\InstalledVersions;
use Exception;
use GustavPHP\Gustav\CLI\{CommandDefinition, Kernel as ConsoleKernel};
use GustavPHP\Gustav\Config\{Environment, Loader};
use GustavPHP\Gustav\Controller\{ControllerFactory, Response};
use GustavPHP\Gustav\Http\Binding\RequestBinder;
use GustavPHP\Gustav\Http\{CallableRequestHandler, RequestId, ResponseHandler};
use GustavPHP\Gustav\Http\Exception\{HttpException, RequestInputException, ValidationException};
use GustavPHP\Gustav\Logger\{ExceptionReporter, JsonLogger};
use GustavPHP\Gustav\Middleware\Pipeline;
use GustavPHP\Gustav\Router\{Method, Router};
use GustavPHP\Gustav\Service\Container;
use InvalidArgumentException;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\EventDispatcher\{EventDispatcherInterface, ListenerProviderInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Log\LoggerInterface;
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
     * @var array<class-string<MiddlewareInterface>>
     */
    protected array $middlewares = [];
    /**
     * @var array<int,RequestBinder>
     */
    protected array $requestBinders = [];
    /**
     * @var array<int,ResponseHandler>
     */
    protected array $responseHandlers = [];

    protected Container $services;
    /** @var list<CommandDefinition> */
    private array $commands = [];

    private ExceptionReporter $fallbackReporter;

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
        $defaultLogger = new JsonLogger();
        $this->fallbackReporter = new ExceptionReporter($defaultLogger, $defaultLogger);
        $eventListeners = Discovery::discoverEventListeners();
        $this->services = new Container();
        $this->services
            ->singleton(Configuration::class, $configuration)
            ->singleton(self::class, $this)
            ->singleton(LoggerInterface::class, $defaultLogger)
            ->scoped(
                ExceptionReporter::class,
                function (Container $services) use ($defaultLogger): ExceptionReporter {
                    $logger = $services->get(LoggerInterface::class);
                    if (!$logger instanceof LoggerInterface) {
                        throw new LogicException('Logger service is invalid');
                    }

                    return new ExceptionReporter($logger, $defaultLogger);
                },
            )
            ->scoped(
                ListenerProviderInterface::class,
                fn (Container $services): ListenerProviderInterface => new Event\ListenerProvider(
                    $services,
                    $eventListeners,
                ),
            )
            ->scoped(
                EventDispatcherInterface::class,
                Event\Dispatcher::class,
            );
        $environment = $configuration->getEnvironment() ?? Environment::system();
        foreach ((new Loader($environment))->load(Discovery::discoverConfigurations()) as $class => $instance) {
            $this->services->singleton($class, $instance);
        }
        Router::reset();
        Serializer\Manager::reset();
        View::reset();

        foreach (Discovery::discoverServices() as $service) {
            $this->services->bind(
                $service->service,
                $service->implementation,
                $service->lifetime,
            );
        }
        foreach (Discovery::discoverServiceProviders() as $provider) {
            (new $provider())->register($this->services);
        }
        foreach ($eventListeners as $listener) {
            $this->services->scoped($listener->listener);
        }
        foreach (Discovery::discoverMiddlewares() as $middleware) {
            $this->services->bind(
                $middleware['class'],
                $middleware['class'],
                $middleware['lifetime'],
            );
            $this->addMiddleware($middleware['class']);
        }
        $commandNames = [];
        foreach (Discovery::discoverCommands() as $class) {
            $definition = CommandDefinition::compile($class);
            if (isset($commandNames[$definition->name])) {
                throw new LogicException(
                    "Command name '{$definition->name}' is declared by both "
                    . "{$commandNames[$definition->name]} and {$class}",
                );
            }
            $commandNames[$definition->name] = $class;
            $this->commands[] = $definition;
        }
        foreach (Discovery::discoverController() as $class) {
            $this->addRoutes([$class]);
        }
        foreach (Discovery::discoverSerializers() as $class) {
            Serializer\Manager::addEntity($class);
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
     *
     * @param class-string<MiddlewareInterface> ...$middlewares
     */
    public function addMiddleware(string ...$middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if (!is_a($middleware, MiddlewareInterface::class, true)) {
                throw new InvalidArgumentException(
                    "Middleware '{$middleware}' must implement " . MiddlewareInterface::class,
                );
            }
        }
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
     * Create the application console with all discovered commands.
     */
    public function console(): ConsoleKernel
    {
        $this->services->build();

        return new ConsoleKernel($this->services, $this->commands, self::$configuration->mode);
    }

    /**
     * Handle one PSR-7 request independently of the server transport.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $scope = null;
        $serverFailureReported = false;
        $requestId = RequestId::fromRequest($request);
        $request = $request->withAttribute(RequestId::ATTRIBUTE, $requestId);

        try {
            $this->services->build();
            $scope = $this->services->createScope([
                RequestId::class => $requestId,
                ServerRequestInterface::class => $request,
            ]);
            $path = ltrim($request->getUri()->getPath(), '/');
            $request = $request->withAttribute('Gustav-Path', $path);
            $scope->setRequest($request);

            $response = (new Pipeline(
                $this->resolveMiddlewares($this->middlewares, $scope),
                new CallableRequestHandler(
                    function (ServerRequestInterface $nextRequest) use (
                        $scope,
                        $requestId,
                        &$serverFailureReported,
                    ): ResponseInterface {
                        return $this->handleRoutedRequestSafely(
                            $nextRequest,
                            $scope,
                            $requestId,
                            $serverFailureReported,
                        );
                    },
                ),
            ))->handle($request);
        } catch (Throwable $th) {
            $this->reportExceptionOnce(
                $th,
                $request,
                $requestId,
                $scope,
                $serverFailureReported,
            );
            $response = $this->renderException($th);
        } finally {
            $scope?->release();
        }

        return $response->withHeader(RequestId::HEADER, (string) $requestId);
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
     * Construct and start an application without imperative instance setup.
     */
    public static function run(Configuration $configuration): void
    {
        (new self($configuration))->start();
    }

    /**
     * Access application service registration before request handling begins.
     */
    public function services(): Container
    {
        return $this->services;
    }

    /**
     * Starts the application.
     *
     * @return void
     */
    public function start(): void
    {
        $this->services->build();
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
    protected function dispatchRequest(
        ServerRequestInterface $request,
        Container $scope,
    ): ResponseInterface {
        $route = $request->getAttribute('Gustav-Route');
        $controller = $request->getAttribute('Gustav-Controller');
        if (!$route instanceof Attribute\Route || !$controller instanceof ControllerFactory) {
            throw new LogicException('Request route has not been initialized');
        }

        $scope->setRequest($request);
        $instance = $scope->make($controller->getClass());
        if (!$instance instanceof Controller\Base) {
            throw new LogicException(
                "Controller '{$controller->getClass()}' must extend " . Controller\Base::class,
            );
        }
        $requestBinder = $this->requestBinders[spl_object_id($route)] ?? null;
        if ($requestBinder === null) {
            throw new LogicException('Request binder has not been initialized');
        }
        $payload = $instance->{$route->getFunction()}(...$requestBinder->bind($request, $route->getPlaceholders()));
        $responseHandler = $this->responseHandlers[spl_object_id($route)] ?? null;
        if ($responseHandler === null) {
            throw new LogicException('Response handler has not been initialized');
        }

        return $responseHandler->respond($payload);
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
    protected function handleRoutedRequest(
        ServerRequestInterface $request,
        Container $scope,
    ): ResponseInterface {
        $scope->setRequest($request);
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

        $middlewares = $this->resolveMiddlewares(
            $controller->getMiddlewareClasses($route->getFunction()),
            $scope,
        );
        $request = $request
            ->withAttribute('Gustav-Route', $route)
            ->withAttribute('Gustav-Controller', $controller);

        return (new Pipeline(
            $middlewares,
            new CallableRequestHandler(
                fn (ServerRequestInterface $nextRequest): ResponseInterface => $this->dispatchRequest(
                    $nextRequest,
                    $scope,
                ),
            ),
        ))->handle($request);
    }

    /**
     * Convert route and controller exceptions inside the application middleware
     * pipeline so outer middleware can inspect the resulting error response.
     */
    protected function handleRoutedRequestSafely(
        ServerRequestInterface $request,
        Container $scope,
        RequestId $requestId,
        bool &$serverFailureReported,
    ): ResponseInterface {
        try {
            return $this->handleRoutedRequest($request, $scope);
        } catch (Throwable $throwable) {
            $this->reportExceptionOnce(
                $throwable,
                $request,
                $requestId,
                $scope,
                $serverFailureReported,
            );

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
        $reflector = new ReflectionClass($class);
        if (!$reflector->isSubclassOf(Controller\Base::class)) {
            throw new InvalidArgumentException(
                "Controller '{$class}' must extend " . Controller\Base::class,
            );
        }
        $controller = new ControllerFactory($class, $reflector);
        $this->addMethods($reflector);
        $this->controllers[$class] = $controller;
    }

    protected function renderException(Throwable $throwable): ResponseInterface
    {
        $status = $this->exceptionStatus($throwable);

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

    private function exceptionStatus(Throwable $throwable): int
    {
        return $throwable instanceof HttpException
            ? $throwable->getStatusCode()
            : 500;
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
        $requestBinder = RequestBinder::compile($method, $route->getPath());
        $responseHandler = ResponseHandler::compile($method);
        $routeId = spl_object_id($route);
        $this->requestBinders[$routeId] = $requestBinder;
        $this->responseHandlers[$routeId] = $responseHandler;
    }

    private function reportExceptionOnce(
        Throwable $throwable,
        ServerRequestInterface $request,
        RequestId $requestId,
        ?Container $scope,
        bool &$serverFailureReported,
    ): void {
        $status = $this->exceptionStatus($throwable);
        if ($status < 500 || $serverFailureReported) {
            return;
        }
        $serverFailureReported = true;

        if ($scope === null) {
            $this->fallbackReporter->report($throwable, $request, $requestId, $status);

            return;
        }

        try {
            $reporter = $scope->get(ExceptionReporter::class);
            if (!$reporter instanceof ExceptionReporter) {
                throw new LogicException('Exception reporter service is invalid');
            }
            $reporter->report($throwable, $request, $requestId, $status);
        } catch (Throwable) {
            $this->fallbackReporter->report($throwable, $request, $requestId, $status);
        }
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $classes
     * @return array<MiddlewareInterface>
     */
    private function resolveMiddlewares(array $classes, Container $scope): array
    {
        return array_map(function (string $class) use ($scope): MiddlewareInterface {
            $middleware = $scope->get($class);
            if (!$middleware instanceof MiddlewareInterface) {
                throw new LogicException(
                    "Service '{$class}' must resolve to " . MiddlewareInterface::class,
                );
            }

            return $middleware;
        }, $classes);
    }
}
