<?php

namespace GustavPHP\Gustav\Http\Binding;

use GustavPHP\Gustav\Attribute\{AuthUser, Body, Cookie, Header, Param, Query, Request, Validate};
use GustavPHP\Gustav\Auth\Identity;
use GustavPHP\Gustav\Http\Binding\Resolver\{AuthUserResolver, BodyResolver, CookieResolver, HeaderResolver, InputResolver, ParamResolver, QueryResolver, RequestResolver};
use GustavPHP\Gustav\Http\Exception\ValidationException;
use GustavPHP\Gustav\Input\{ConversionResult, TypeConverter};
use GustavPHP\Gustav\Validation\{Validation, Violation};
use LogicException;
use Psr\Http\Message\{ServerRequestInterface};
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final readonly class RequestBinder
{
    /**
     * @param list<ArgumentMetadata> $arguments
     */
    private function __construct(private array $arguments)
    {
    }

    /**
     * @param array<string,int> $placeholders
     * @return array<string,mixed>
     */
    public function bind(ServerRequestInterface $request, array $placeholders): array
    {
        $context = new BindingContext($request, $placeholders);
        $bound = [];
        $violations = [];

        foreach ($this->arguments as $argument) {
            $resolution = $argument->resolver->resolve($context);
            $source = $argument->resolver->getSource()->value;
            $path = $argument->resolver->getPath() ?: $argument->name;

            if (!$resolution->isPresent) {
                if (!$argument->hasDefault) {
                    $violations[] = new Violation($source, $path, 'required', 'Value is required');
                }

                continue;
            }

            $result = $argument->converter === null
                ? ConversionResult::success($resolution->value)
                : $argument->converter->convert(
                    $resolution->value,
                    $source,
                    $argument->resolver->getPath(),
                );
            if (!$result->isValid) {
                array_push($violations, ...$result->violations);

                continue;
            }

            foreach ($argument->rules as $rule) {
                $ruleViolation = $rule->getViolation($result->value);
                if ($ruleViolation !== null) {
                    $violations[] = new Violation(
                        $source,
                        $path,
                        $ruleViolation->code,
                        $ruleViolation->message,
                    );
                }
            }

            $bound[$argument->name] = $result->value;
        }

        if ($violations !== []) {
            throw new ValidationException($violations);
        }

        return $bound;
    }

    public static function compile(ReflectionMethod $method, string $routePath): self
    {
        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            $arguments[] = self::compileArgument($method, $parameter, $routePath);
        }

        return new self($arguments);
    }

    private static function assertIdentityType(ReflectionParameter $parameter, string $location): void
    {
        $type = $parameter->getType();
        if (
            !$type instanceof ReflectionNamedType
            || $type->isBuiltin()
            || !is_a($type->getName(), Identity::class, true)
        ) {
            throw new LogicException("{$location} must implement " . Identity::class);
        }
    }

    private static function assertRequestType(ReflectionParameter $parameter, string $location): void
    {
        $type = $parameter->getType();
        if (
            !$type instanceof ReflectionNamedType
            || $type->isBuiltin()
            || !is_a(ServerRequestInterface::class, $type->getName(), true)
        ) {
            throw new LogicException("{$location} must accept " . ServerRequestInterface::class);
        }
    }

    private static function compileArgument(
        ReflectionMethod $method,
        ReflectionParameter $parameter,
        string $routePath,
    ): ArgumentMetadata {
        $attributes = self::sourceAttributes($parameter);
        $location = "Controller parameter {$method->getDeclaringClass()->getName()}::{$method->getName()}(\${$parameter->getName()})";
        if (count($attributes) !== 1) {
            throw new LogicException("{$location} must declare exactly one request input attribute");
        }

        $attribute = $attributes[0]->newInstance();
        $resolver = self::resolver($attribute, $routePath, $location);
        $converter = null;

        if ($attribute instanceof Request) {
            self::assertRequestType($parameter, $location);
        } elseif ($attribute instanceof AuthUser) {
            self::assertIdentityType($parameter, $location);
        } else {
            $converter = TypeConverter::fromReflection(
                $parameter->getType(),
                $location,
                $attribute instanceof Body || $attribute instanceof Query,
            );

            if (self::resolvesWholeSource($attribute) && !$converter->acceptsWholeInput()) {
                throw new LogicException("{$location} must use an array or DTO when no input key is specified");
            }
        }

        return new ArgumentMetadata(
            $parameter->getName(),
            $resolver,
            $converter,
            $parameter->isDefaultValueAvailable(),
            self::rules($parameter),
        );
    }

    /**
     * @return list<string>
     */
    private static function placeholderNames(string $routePath): array
    {
        preg_match_all('/\{([^{}]+)\}/', $routePath, $matches);

        return $matches[1];
    }

    private static function resolver(object $attribute, string $routePath, string $location): InputResolver
    {
        if ($attribute instanceof Body) {
            return new BodyResolver($attribute->hasKey() ? $attribute->getKey() : null);
        }
        if ($attribute instanceof Query) {
            return new QueryResolver($attribute->hasKey() ? $attribute->getKey() : null);
        }
        if ($attribute instanceof Param) {
            $name = $attribute->hasName() ? $attribute->getName() : null;
            if ($name !== null && !in_array($name, self::placeholderNames($routePath), true)) {
                throw new LogicException("{$location} references unknown route parameter {$name}");
            }

            return new ParamResolver($name);
        }
        if ($attribute instanceof Header) {
            return new HeaderResolver($attribute->hasName() ? $attribute->getName() : null);
        }
        if ($attribute instanceof Cookie) {
            return new CookieResolver($attribute->hasKey() ? $attribute->getKey() : null);
        }
        if ($attribute instanceof Request) {
            return new RequestResolver();
        }
        if ($attribute instanceof AuthUser) {
            return new AuthUserResolver();
        }

        throw new LogicException("{$location} uses an unsupported request input attribute");
    }

    private static function resolvesWholeSource(object $attribute): bool
    {
        return ($attribute instanceof Body && !$attribute->hasKey())
            || ($attribute instanceof Query && !$attribute->hasKey())
            || ($attribute instanceof Param && !$attribute->hasName())
            || ($attribute instanceof Header && !$attribute->hasName())
            || ($attribute instanceof Cookie && !$attribute->hasKey());
    }

    /**
     * @return list<Validation>
     */
    private static function rules(ReflectionParameter $parameter): array
    {
        return array_map(
            function (ReflectionAttribute $attribute): Validation {
                /** @var Validate $validation */
                $validation = $attribute->newInstance();

                return $validation->rule;
            },
            $parameter->getAttributes(Validate::class),
        );
    }

    /**
     * @return list<ReflectionAttribute<object>>
     */
    private static function sourceAttributes(ReflectionParameter $parameter): array
    {
        $attributes = [];
        foreach ([Body::class, Query::class, Param::class, Header::class, Cookie::class, Request::class, AuthUser::class] as $class) {
            array_push($attributes, ...$parameter->getAttributes($class));
        }

        return $attributes;
    }
}
