<?php

namespace GustavPHP\Gustav\Http;

use BackedEnum;
use GustavPHP\Gustav\Controller\{Response, ResponseFormat};
use GustavPHP\Gustav\Serializer\Manager;
use JsonSerializable;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use ReflectionNamedType;
use UnitEnum;

final readonly class ResponseHandler
{
    private function __construct(private bool $serializeAsJson)
    {
    }

    public static function compile(ReflectionMethod $method): self
    {
        $location = "Controller method {$method->getDeclaringClass()->getName()}::{$method->getName()}()";
        $type = $method->getReturnType();
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("{$location} must declare one response type");
        }

        if (self::isResponseObject($type)) {
            if ($type->allowsNull()) {
                throw new LogicException("{$location} must return a non-null response object");
            }

            return new self(false);
        }

        self::assertJsonType($type, $location);

        return new self(true);
    }

    public function respond(mixed $payload): ResponseInterface
    {
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

    private static function isResponseObject(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }
        $name = $type->getName();

        return is_a($name, Response::class, true) || is_a($name, ResponseInterface::class, true);
    }
}
