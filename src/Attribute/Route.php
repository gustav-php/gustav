<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use Exception;
use GustavPHP\Gustav\Message\RequestInterface;
use GustavPHP\Gustav\Router\Method;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    protected ?string $class;
    protected ?string $function;
    protected array $params = [];
    protected array $placeholders = [];

    public function __construct(protected string $path, protected Method $method = Method::GET)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function setFunction(string $function): self
    {
        $this->function = $function;

        return $this;
    }

    public function getParam(string $name): ?Param
    {
        return $this->params[$name] ?? null;
    }

    public function addPlaceholder(string $key, int $index): void
    {
        $this->placeholders[$key] = $index;
    }

    public function generateParams(RequestInterface $request): array
    {
        $pathParams = [];
        $parts = explode('/', $request->getPath());

        foreach ($this->placeholders as $key => $index) {
            $pathParams[$key] = $parts[$index];
        }
        /**
         * Merge Path and Query Parameters with Post Data (Path > Query > Post).
         */
        $params = \array_merge($pathParams, $request->getQueryParameters(), $request->getPostData());

        return \array_reduce($this->params, function (array $carry, Param $param) use ($params) {
            if (\array_key_exists($param->getName(), $params)) {
                $carry[$param->getParameter()] = $params[$param->getName()];
            } elseif ($param->getRequired()) {
                throw new Exception("Parameter '{$param->getName()}' is required.", 400);
            }

            return $carry;
        }, []);
    }

    public function addParam(string $name, Param $param): self
    {
        $this->params[$name] = $param;

        return $this;
    }
}
