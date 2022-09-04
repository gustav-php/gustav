<?php

namespace TorstenDittmann\Gustav\Attributes;

use Attribute;
use Exception;
use Sabre\HTTP\Request;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public const GET = 'GET';

    public const POST = 'POST';

    protected ?string $class;

    protected ?string $function;

    protected array $params = [];

    public function __construct(protected string $path, protected string $method = self::GET)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
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

    public function generateParams(Request $request): array
    {
        return \array_reduce($this->params, function (array $carry, Param $param) use ($request) {
            /**
             * Merge Query Parameters with Post Data (Query > Post).
             */
            $queryParams = \array_merge($request->getQueryParameters(), $request->getPostData());

            if (\array_key_exists($param->getName(), $queryParams)) {
                $carry[$param->getParameter()] = $queryParams[$param->getName()];
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
