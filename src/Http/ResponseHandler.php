<?php

namespace GustavPHP\Gustav\Http;

use BackedEnum;
use GustavPHP\Gustav\Controller\{Response, ResponseFormat};
use GustavPHP\Gustav\Serializer\Manager;
use GustavPHP\Gustav\View;
use GustavPHP\Gustav\View\ViewRendererInterface;
use JsonSerializable;
use LogicException;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use ReflectionNamedType;
use UnitEnum;

final readonly class ResponseHandler
{
    private function __construct(
        private bool $serializeAsJson,
        private bool $renderAsView,
    ) {
    }

    public static function compile(ReflectionMethod $method): self
    {
        $location = "Controller method {$method->getDeclaringClass()->getName()}::{$method->getName()}()";

        return self::compileMethod($method, $location, true);
    }

    /** @internal */
    public static function compileExplicit(ReflectionMethod $method, string $location): self
    {
        return self::compileMethod($method, $location, false);
    }

    public function requiresViewRenderer(): bool
    {
        return $this->renderAsView;
    }

    public function respond(
        mixed $payload,
        ?ViewRendererInterface $viewRenderer = null,
    ): ResponseInterface {
        if ($this->renderAsView) {
            if (!$payload instanceof View || $viewRenderer === null) {
                throw new LogicException('Controller returned a value that does not match its compiled response type');
            }

            return new Psr7Response(
                $payload->status,
                array_merge($payload->headers, ['Content-Type' => 'text/html; charset=utf-8']),
                $viewRenderer->render($payload),
            );
        }

        if ($this->serializeAsJson) {
            return (new Response(
                body: $payload,
                format: ResponseFormat::Json,
            ))->build();
        }

        if ($payload instanceof Response) {
            return $payload->build();
        }
        if ($payload instanceof ResponseInterface) {
            return $payload;
        }

        throw new LogicException('Controller returned a value that does not match its compiled response type');
    }

    private static function assertJsonType(ReflectionNamedType $type, string $location): void
    {
        $name = $type->getName();
        if ($type->isBuiltin()) {
            if (!in_array($name, ['array', 'bool', 'false', 'float', 'int', 'null', 'string', 'true'], true)) {
                throw new LogicException("{$location} declares an unsupported inferred JSON response type {$name}");
            }

            return;
        }

        if (in_array($name, ['parent', 'self', 'static'], true)) {
            throw new LogicException("{$location} declares an unsupported inferred JSON response type {$name}");
        }
        if (is_a($name, UnitEnum::class, true) && !is_a($name, BackedEnum::class, true)) {
            throw new LogicException("{$location} cannot serialize an unbacked enum");
        }
        if (is_a($name, BackedEnum::class, true) || is_a($name, JsonSerializable::class, true)) {
            return;
        }

        /** @var class-string<object> $name */
        Manager::prepare($name);
    }

    private static function compileMethod(
        ReflectionMethod $method,
        string $location,
        bool $inferJson,
    ): self {
        $type = $method->getReturnType();
        if (!$type instanceof ReflectionNamedType) {
            $response = $inferJson ? 'response' : 'explicit response';

            throw new LogicException("{$location} must declare one {$response} type");
        }

        if (self::isView($type)) {
            if ($type->allowsNull()) {
                throw new LogicException("{$location} must return a non-null view");
            }

            return new self(false, true);
        }

        if (self::isResponseObject($type)) {
            if ($type->allowsNull()) {
                throw new LogicException("{$location} must return a non-null response object");
            }

            return new self(false, false);
        }

        if (!$inferJson) {
            throw new LogicException(
                "{$location} must return Response, ResponseInterface, or View",
            );
        }

        self::assertJsonType($type, $location);

        return new self(true, false);
    }

    private static function isResponseObject(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }
        $name = $type->getName();

        return is_a($name, Response::class, true) || is_a($name, ResponseInterface::class, true);
    }

    private static function isView(ReflectionNamedType $type): bool
    {
        return !$type->isBuiltin() && is_a($type->getName(), View::class, true);
    }
}
