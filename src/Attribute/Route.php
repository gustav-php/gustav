<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Router\Method;
use Psr\Http\Message\ServerRequestInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    protected ?string $class;
    protected ?string $function;
    /**
     * @var array<string,Param>
     */
    protected array $params = [];
    /**
     * @var array<string,int>
     */
    protected array $placeholders = [];

    public function __construct(protected string $path, protected Method $method = Method::GET)
    {
    }

    public function addParam(string $name, Param $param): self
    {
        $this->params[$name] = $param;

        return $this;
    }

    public function addPlaceholder(string $key, int $index): void
    {
        $this->placeholders[$key] = $index;
    }

    /**
     * Generate the parameters for the Route.
     *
     * @param ServerRequestInterface $request
     * @return array<string,mixed>
     */
    public function generateParams(ServerRequestInterface $request): array
    {
        $pathParams = [];
        $path = trim($request->getUri()->getPath(), "/");
        $parts = explode('/', $path);
        foreach ($this->placeholders as $key => $index) {
            $pathParams[$key] = $parts[$index];
        }

        /**
         * Merge Path and Query Parameters with Post Data (Path > Query > Post).
         */
        $params = \array_merge($pathParams, $request->getQueryParams(), (array) ($request->getParsedBody() ?? []));

        return \array_reduce($this->params, function (array $carry, Param $param) use ($params) {
            if (\array_key_exists($param->getName(), $params)) {
                $carry[$param->getParameter()] = $params[$param->getName()];
            } elseif ($param->getRequired()) {
                throw new \Exception("Parameter '{$param->getName()}' is required.", 400);
            }

            return $carry;
        }, []);
    }

    public function getClass(): string
    {
        if ($this->class === null) {
            throw new \Exception('Class not set');
        }
        return $this->class;
    }

    public function getFunction(): string
    {
        if ($this->function === null) {
            throw new \Exception('Function not set');
        }
        return $this->function;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getParam(string $name): ?Param
    {
        return $this->params[$name] ?? null;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function setFunction(string $function): self
    {
        $this->function = $function;

        return $this;
    }
}
