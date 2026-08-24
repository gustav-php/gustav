<?php

namespace GustavPHP\Gustav;

use Composer\InstalledVersions;
use Exception;
use GustavPHP\Gustav\CLI\{CommandDefinition, Kernel as ConsoleKernel};
use GustavPHP\Gustav\Config\{Environment, Loader};
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Http\{CallableRequestHandler, RequestId};
use GustavPHP\Gustav\Http\Exception\{HttpException, RequestInputException, ValidationException};
use GustavPHP\Gustav\Logger\{ExceptionReporter, JsonLogger};
use GustavPHP\Gustav\Middleware\Pipeline;
use GustavPHP\Gustav\Router\{Method, RouteCompiler, RouteMatch, Router, UrlGeneratorInterface};
use GustavPHP\Gustav\Security\{CsrfMiddleware, CsrfTokenManager};
use GustavPHP\Gustav\Service\{Container, Lifetime};
use GustavPHP\Gustav\Session\{FileSessionStore, SessionMiddleware, SessionOptions, SessionStoreInterface};
use GustavPHP\Gustav\View\{PhpViewRenderer, ViewRendererInterface};
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
     * @var array<string,string>
     */
    protected array $files = [];
    /**
     * @var array<class-string<MiddlewareInterface>>
     */
    protected array $middlewares = [];

    protected Container $services;
    /** @var list<CommandDefinition> */
    private array $commands = [];

    private PhpViewRenderer $exceptionViews;

    private ExceptionReporter $fallbackReporter;

    private Router $router;

    private bool $sessionsEnabled;

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
        $this->exceptionViews = new PhpViewRenderer(__DIR__ . '/../views');
        $defaultLogger = new JsonLogger();
        $this->fallbackReporter = new ExceptionReporter($defaultLogger, $defaultLogger);
        $eventListeners = Discovery::discoverEventListeners();
        Serializer\Manager::reset();
        $routes = [];
        foreach (Discovery::discoverControllers() as $class) {
            array_push($routes, ...RouteCompiler::compile($class));
        }
        $this->sessionsEnabled = $configuration->session !== null;
        if (!$this->sessionsEnabled) {
            foreach ($routes as $route) {
                if ($route->csrfProtected) {
                    throw new LogicException(
                        "CSRF-protected route {$route->location()} requires session configuration",
                    );
                }
            }
        }
        $this->router = new Router($routes);
        $this->services = new Container();
        $this->services
            ->singleton(Configuration::class, $configuration)
            ->singleton(self::class, $this)
            ->singleton(Router::class, $this->router)
            ->singleton(UrlGeneratorInterface::class, $this->router)
            ->singleton(ViewRendererInterface::class, new PhpViewRenderer($configuration->views))
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
        if ($configuration->session !== null) {
            $this->services
                ->singleton(SessionOptions::class, $configuration->session)
                ->singleton(SessionStoreInterface::class, FileSessionStore::class)
                ->scoped(Session::class)
                ->scoped(SessionMiddleware::class)
                ->scoped(CsrfTokenManager::class)
                ->scoped(CsrfMiddleware::class);
        }
        $environment = $configuration->getEnvironment() ?? Environment::system();
        foreach ((new Loader($environment))->load(Discovery::discoverConfigurations()) as $class => $instance) {
            $this->services->singleton($class, $instance);
        }
        foreach (Discovery::discoverServices() as $service) {
            $this->services->bind(
                $service->service,
                $service->implementation,
                $service->lifetime,
            );
        }
        foreach (Discovery::discoverServiceFactories() as $factory) {
            match ($factory->lifetime) {
                Lifetime::Singleton => $this->services->singleton($factory->service, $factory->resolver()),
                Lifetime::Scoped => $this->services->scoped($factory->service, $factory->resolver()),
                Lifetime::Transient => $this->services->transient($factory->service, $factory->resolver()),
            };
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
                    $relative = ltrim(substr($file->getPathname(), strlen($configuration->files)), '/\\');
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
            $path = $request->getUri()->getPath();
            $request = $request->withAttribute('Gustav-Path', $path);
            $scope->setRequest($request);

            $middlewares = $this->resolveMiddlewares($this->middlewares, $scope);
            if ($this->sessionsEnabled) {
                array_unshift($middlewares, $this->resolveSessionMiddleware($scope));
            }

            $response = (new Pipeline(
                $middlewares,
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

        if ($request->getMethod() === Method::HEAD->value) {
            $response = $response->withBody((new Psr17Factory())->createStream());
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
     * Invoke the matched controller.
     */
    protected function dispatchRequest(
        ServerRequestInterface $request,
        Container $scope,
        RouteMatch $match,
    ): ResponseInterface {
        $route = $match->route;
        $scope->setRequest($request);
        $instance = $scope->make($route->controller);
        $payload = $instance->{$route->handler}(...$route->requestBinder->bind($request, $match->parameters));

        $viewRenderer = null;
        if ($route->responseHandler->requiresViewRenderer()) {
            $viewRenderer = $scope->get(ViewRendererInterface::class);
            if (!$viewRenderer instanceof ViewRendererInterface) {
                throw new LogicException('View renderer service is invalid');
            }
        }

        return $route->responseHandler->respond($payload, $viewRenderer);
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

        $filePath = ltrim($path, '/');
        if (
            in_array($request->getMethod(), [Method::GET->value, Method::HEAD->value], true)
            && array_key_exists($filePath, $this->files)
        ) {
            return $this->serveStaticFile($this->files[$filePath]);
        }

        $method = Method::fromRequest($request);
        if ($method === Method::OPTIONS && !$this->router->hasExplicitRoute($method, $path)) {
            $allowed = $this->router->allowedMethods($path);
            if ($allowed !== []) {
                return new Psr7Response(
                    204,
                    ['Allow' => implode(', ', array_map(fn (Method $item): string => $item->value, $allowed))],
                );
            }
        }

        $match = $this->router->match($method, $path);
        $middlewares = $this->resolveMiddlewares($match->route->middlewares, $scope);
        if ($match->route->csrfProtected) {
            array_unshift($middlewares, $this->resolveCsrfMiddleware($scope));
        }
        $request = $request
            ->withAttribute('Gustav-Route', $match->route)
            ->withAttribute('Gustav-Route-Parameters', $match->parameters);

        return (new Pipeline(
            $middlewares,
            new CallableRequestHandler(
                fn (ServerRequestInterface $nextRequest): ResponseInterface => $this->dispatchRequest(
                    $nextRequest,
                    $scope,
                    $match,
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
                body: $this->exceptionViews->render(new View('exception', [
                    'title' => get_class($throwable),
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'code' => $status,
                    'trace' => $this->prepareTrace($throwable),
                    'snippet' => $this->getCodeBlockFromTrace($throwable->getFile(), $throwable->getLine()),
                    'version' => InstalledVersions::getPrettyVersion('gustav-php/gustav'),
                ])),
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

    private function resolveCsrfMiddleware(Container $scope): CsrfMiddleware
    {
        $middleware = $scope->get(CsrfMiddleware::class);
        if (!$middleware instanceof CsrfMiddleware) {
            throw new LogicException('CSRF middleware service is invalid');
        }

        return $middleware;
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

    private function resolveSessionMiddleware(Container $scope): SessionMiddleware
    {
        $middleware = $scope->get(SessionMiddleware::class);
        if (!$middleware instanceof SessionMiddleware) {
            throw new LogicException('Session middleware service is invalid');
        }

        return $middleware;
    }
}
