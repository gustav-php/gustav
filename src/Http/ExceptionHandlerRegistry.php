<?php

namespace GustavPHP\Gustav\Http;

use GustavPHP\Gustav\Service\Container;
use GustavPHP\Gustav\View\ViewRendererInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/** @internal */
final readonly class ExceptionHandlerRegistry
{
    /** @var array<class-string<Throwable>, ExceptionHandlerDefinition> */
    private array $handlers;

    /** @param list<ExceptionHandlerDefinition> $definitions */
    public function __construct(array $definitions)
    {
        usort(
            $definitions,
            fn (ExceptionHandlerDefinition $left, ExceptionHandlerDefinition $right): int =>
                strcmp($left->exception, $right->exception)
                ?: strcmp($left->handler, $right->handler),
        );

        $handlers = [];
        $previous = null;
        foreach ($definitions as $definition) {
            if ($previous !== null && $previous->exception === $definition->exception) {
                throw new LogicException(sprintf(
                    "Exception type '%s' is handled by both %s and %s",
                    $definition->exception,
                    $previous->handler,
                    $definition->handler,
                ));
            }

            $handlers[$definition->exception] = $definition;
            $previous = $definition;
        }

        $this->handlers = $handlers;
    }

    public function handle(Throwable $throwable, Container $scope): ?ResponseInterface
    {
        $definition = $this->definitionFor($throwable);
        if ($definition === null) {
            return null;
        }

        $handler = $scope->get($definition->handler);
        $class = $definition->handler;
        if (!$handler instanceof $class || !is_callable($handler)) {
            throw new LogicException("Exception handler '{$class}' did not resolve to an invokable instance");
        }

        $payload = $handler($throwable);
        $viewRenderer = null;
        if ($definition->responseHandler->requiresViewRenderer()) {
            $viewRenderer = $scope->get(ViewRendererInterface::class);
            if (!$viewRenderer instanceof ViewRendererInterface) {
                throw new LogicException('View renderer service is invalid');
            }
        }

        return $definition->responseHandler->respond($payload, $viewRenderer);
    }

    private function definitionFor(Throwable $throwable): ?ExceptionHandlerDefinition
    {
        $exception = $throwable::class;
        while (true) {
            if (isset($this->handlers[$exception])) {
                return $this->handlers[$exception];
            }

            $parent = get_parent_class($exception);
            if ($parent === false) {
                break;
            }
            $exception = $parent;
        }

        return $this->handlers[Throwable::class] ?? null;
    }
}
