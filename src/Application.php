<?php

namespace GustavPHP\Gustav;

use Composer\InstalledVersions;
use Exception;
use GustavPHP\Gustav\Controller\{ControllerFactory, Response};
use GustavPHP\Gustav\Router\{Method, Router};
use GustavPHP\Gustav\Service\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;
use SplFileInfo;
use stdClass;
use Throwable;

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
                $request = $this->initMiddleware($request);
                $request = $this->customMiddleware($request);
                if ($request instanceof Psr7Response) {
                    $psr7->respond($request);
                    break;
                }

                $response = $this->handleRequest($request);

                $psr7->respond($response);
            } catch (\Throwable $e) {
                var_dump($e);
                // In case of any exceptions in the application code, you should handle
                // them and inform the client about the presence of a server error.
                //
                // Reply by the 500 Internal Server Error response
                $psr7->respond(new Psr7Response(500, [], 'Something Went Wrong!'));
                $psr7->getWorker()->error((string)$e);
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
     * Handles custom middlewares.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface|Psr7Response
     */
    protected function customMiddleware(ServerRequestInterface $request): ServerRequestInterface|Psr7Response
    {
        /**
         * @var Middleware\Base[] $middlewares
         */
        $middlewares = $request->getAttribute('Gustav-Middlewares', []);
        foreach ($middlewares as $middleware) {
            $request = $middleware->handle($request);
            if ($request instanceof Response) {
                return $request->build();
            }
        }
        return $request;
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
        $lines = file($file);
        $code = '';
        $code .= $lines[$line - 5] ?? null;
        $code .= $lines[$line - 4] ?? null;
        $code .= $lines[$line - 3] ?? null;
        $code .= $lines[$line - 2] ?? null;
        $code .= $lines[$line - 1] ?? null; // current line
        $code .= $lines[$line] ?? null;
        $code .= $lines[$line + 1] ?? null;
        $code .= $lines[$line + 2] ?? null;
        $code .= $lines[$line + 3] ?? null;

        return $code;
    }

    /**
     * Handles a given request.
     *
     * @param ServerRequestInterface $request
     * @return Psr7Response
     * @throws Throwable
     */
    protected function handleRequest(ServerRequestInterface $request): Psr7Response
    {
        $response = new Response();

        try {
            $context = new Context(
                path: $request->getAttribute('Gustav-Path'),
                route: $request->getAttribute('Gustav-Route'),
                controllerFactory: $request->getAttribute('Gustav-Controller')
            );
            if ($request->getMethod() === 'GET' && array_key_exists($context->path, $this->files)) {
                $path = $this->files[$context->path];
                $contentType = mime_content_type($path);
                return (new Response(
                    status: 200,
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
                    throw new Exception(code: 500);
                }
            }
            $dependencies = new Container();
            $dependencies->addDependency([ServerRequestInterface::class => fn () => $request]);
            $dependencies->build();
            $instance = $dependencies->make($context->controllerFactory->getClass());
            $payload = $instance->{$context->route->getFunction()}(...$context->route->generateArguments($request));
            if (!$payload instanceof Controller\Response) {
                throw new Exception('Controller needs to return a Response object');
            }
            $serializer = $payload->getSerializer();
            if ($serializer) {
                $payload->setBody(Serializer\Manager::getEntity($serializer::class)->serialize($serializer));
                $payload->setBody(json_encode($payload->getBody()));
            }
            return $response->merge($payload)->build();
        } catch (Throwable $th) {
            if ($th->getCode() === 0) {
                $response->setStatus(500);
            } else {
                $response->setStatus($th->getCode());
            }
            if (self::isProduction()) {
                $response->setBody(
                    $th->getCode() >= 500
                        ? 'Server Error'
                        : $th->getMessage()
                );
            } else {
                return (new Response(
                    body: View::render(__DIR__ . '/../views/exception.latte', [
                        'title' => get_class($th),
                        'exception' => get_class($th),
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'code' => $th->getCode(),
                        'trace' => $this->prepareTrace($th),
                        'snippet' => $this->getCodeBlockFromTrace($th->getFile(), $th->getLine()),
                        'version' => InstalledVersions::getPrettyVersion('gustav-php/gustav')
                    ]),
                    status: $th->getCode() >= 500 ? 500 : (int) $th->getCode()
                ))->buildHtml();
            }
            return $response->buildJson();
        }
    }

    /**
     * Initializes the application middleware.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function initMiddleware(ServerRequestInterface $request): ServerRequestInterface
    {
        try {
            $path = ltrim($request->getUri()->getPath(), '/');
            $request = $request->withAttribute('Gustav-Path', $path);
            $route = Router::match(Method::fromRequest($request), $path);
            $controller = $this->controllers[$route->getClass()];

            return $request
                ->withAttribute('Gustav-Route', $route)
                ->withAttribute('Gustav-Controller', $controller)
                ->withAttribute('Gustav-Middlewares', $controller->getMiddlewares());
        } catch (Throwable $th) {
            return $request
                ->withAttribute('Gustav-Route', null)
                ->withAttribute('Gustav-Exception', $th);
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
            $request = $parameter->getAttributes(Attribute\Request::class)[0] ?? null;
            if ($request) {
                /** @var Attribute\Request $instance */
                $instance = $request->newInstance();
                $route->addArgument($parameter->getName(), $instance);
                continue;
            }
            $cookie = $parameter->getAttributes(Attribute\Cookie::class)[0] ?? null;
            if ($cookie) {
                /** @var Attribute\Cookie $instance */
                $instance = $cookie->newInstance();
                $instance->setRequired(!$parameter->isOptional());
                $route->addArgument($parameter->getName(), $instance);
                continue;
            }
            $header = $parameter->getAttributes(Attribute\Header::class)[0] ?? null;
            if ($header) {
                /** @var Attribute\Header $instance */
                $instance = $header->newInstance();
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
            var_dump('---');
            var_dump($method, $parameter, $header);
            var_dump('---');

            throw new Exception('Invalid parameter type');
        }
    }
}
