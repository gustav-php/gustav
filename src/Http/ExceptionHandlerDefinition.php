<?php

namespace GustavPHP\Gustav\Http;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Http\Exception\HttpException;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/** @internal */
final readonly class ExceptionHandlerDefinition
{
    /**
     * @param class-string $handler
     * @param class-string<Throwable> $exception
     */
    private function __construct(
        public string $handler,
        public string $exception,
        public ResponseHandler $responseHandler,
    ) {
    }

    /** @param class-string $handler */
    public static function compile(string $handler): self
    {
        $reflection = new ReflectionClass($handler);
        $location = "Exception handler '{$handler}'";

        if (!$reflection->isInstantiable()) {
            throw new LogicException("{$location} must be instantiable");
        }

        $attributes = $reflection->getAttributes(ExceptionHandler::class);
        if (count($attributes) !== 1) {
            throw new LogicException("{$location} must declare exactly one #[ExceptionHandler] attribute");
        }
        if (!$reflection->hasMethod('__invoke')) {
            throw new LogicException("{$location} must declare a public __invoke() method");
        }

        $invoke = $reflection->getMethod('__invoke');
        if (!$invoke->isPublic() || $invoke->isStatic()) {
            throw new LogicException("{$location} must declare a public non-static __invoke() method");
        }
        if ($invoke->getNumberOfParameters() !== 1) {
            throw new LogicException("{$location}::__invoke() must accept exactly one exception parameter");
        }

        $parameter = $invoke->getParameters()[0];
        if ($parameter->isPassedByReference() || $parameter->isVariadic()) {
            throw new LogicException(
                "{$location}::__invoke() exception parameter must be a regular value parameter",
            );
        }

        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new LogicException("{$location}::__invoke() must declare one exception class");
        }
        if ($type->allowsNull()) {
            throw new LogicException("{$location}::__invoke() exception parameter cannot be nullable");
        }

        $exception = $type->getName();
        if (!class_exists($exception) && !interface_exists($exception)) {
            throw new LogicException(
                "{$location}::__invoke() exception type '{$exception}' does not exist",
            );
        }
        if (!is_a($exception, Throwable::class, true)) {
            throw new LogicException(
                "{$location}::__invoke() exception type '{$exception}' must implement Throwable",
            );
        }
        if (is_a($exception, HttpException::class, true)) {
            throw new LogicException("{$location} cannot target framework HTTP exceptions");
        }

        /** @var class-string<Throwable> $exception */
        return new self(
            $handler,
            $exception,
            ResponseHandler::compileExplicit($invoke, "{$location}::__invoke()"),
        );
    }
}
