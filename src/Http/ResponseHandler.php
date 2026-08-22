<?php

namespace GustavPHP\Gustav\Http;

use BackedEnum;
use GustavPHP\Gustav\Attribute\JsonResponse;
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
    private function __construct(private ?JsonResponse $jsonResponse)
    {
    }

    public static function compile(ReflectionMethod $method): self
    {
        $location = "Controller method {$method->getDeclaringClass()->getName()}::{$method->getName()}()";
        $type = $method->getReturnType();
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("{$location} must declare one response type");
        }

        $attributes = $method->getAttributes(JsonResponse::class);
        /** @var JsonResponse|null $jsonResponse */
        $jsonResponse = $attributes === [] ? null : $attributes[0]->newInstance();

        if ($jsonResponse === null) {
            self::assertResponseObject($type, $location);
        } else {
            self::assertJsonType($type, $location);
        }

        return new self($jsonResponse);
    }

    public function respond(mixed $payload): ResponseInterface
    {
        if ($this->jsonResponse !== null) {
            return (new Response(
                status: $this->jsonResponse->status,
                headers: $this->jsonResponse->headers,
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
                throw new LogicException("{$location} declares an unsupported JsonResponse type {$name}");
            }

            return;
        }

        if (is_a($name, Response::class, true) || is_a($name, ResponseInterface::class, true)) {
            throw new LogicException("{$location} cannot use JsonResponse with a response object return type");
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

    private static function assertResponseObject(ReflectionNamedType $type, string $location): void
    {
        if ($type->allowsNull()) {
            throw new LogicException("{$location} must return a non-null response object");
        }

        $name = $type->getName();
        if (
            in_array($name, ['parent', 'self', 'static'], true)
            || $type->isBuiltin()
            || (!is_a($name, Response::class, true) && !is_a($name, ResponseInterface::class, true))
        ) {
            throw new LogicException("{$location} must return a response object or use JsonResponse");
        }
    }
}
