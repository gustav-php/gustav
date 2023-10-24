<?php

namespace GustavPHP\Gustav\Attribute;

use function array_key_exists;

use Attribute;
use Exception;
use GustavPHP\Gustav\Router\Method;
use Psr\Http\Message\ServerRequestInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    /**
     * @var array<string,Query|Body|Param>
     */
    protected array $arguments = [];
    /**
     * @var null|int
     */
    protected ?int $body = null;
    /**
     * @var null|string
     */
    protected ?string $class;
    /**
     * @var null|string
     */
    protected ?string $function;
    /**
     * @var array<string,int>
     */
    protected array $placeholders = [];
    protected ?int $query = null;

    public function __construct(protected string $path, protected Method $method = Method::GET)
    {
    }

    public function addArgument(string $name, Query|Body|Param $type): self
    {
        $this->arguments[$name] = $type;

        return $this;
    }

    public function addPlaceholder(string $key, int $index): void
    {
        $this->placeholders[$key] = $index;
    }

    /**
     * Generate the arguments for the Route.
     *
     * @param ServerRequestInterface $request
     * @return array<string,mixed>
     * @throws Exception
     * @throws Exception
     */
    public function generateArguments(ServerRequestInterface $request): array
    {
        $params = [];
        $path = trim($request->getUri()->getPath(), "/");
        $parts = explode('/', $path);
        foreach ($this->placeholders as $key => $index) {
            $params[$key] = $parts[$index];
        }

        $arguments = [];
        foreach ($this->arguments as $argument => $attribute) {
            switch (get_class($attribute)) {
                case Body::class: {
                    $body = (array) ($request->getParsedBody() ?? []);
                    if ($attribute->hasKey()) {
                        if (!array_key_exists($attribute->getKey(), $body)) {
                            throw new Exception("Body parameter '{$attribute->getKey()}' is required.", 400);
                        }
                        $arguments[$argument] = $body[$attribute->getKey()];
                    } else {
                        $arguments[$argument] = $body;
                    }
                    break;
                }
                case Query::class: {
                    $query = $request->getQueryParams();
                    if ($attribute->hasKey()) {
                        if (!array_key_exists($attribute->getKey(), $query)) {
                            throw new Exception("Query parameter '{$attribute->getKey()}' is required.", 400);
                        }
                        $arguments[$argument] = $attribute->hasDto()
                            ? $attribute->getDto()->build($request->getQueryParams()[$attribute->getKey()])
                            : $request->getQueryParams()[$attribute->getKey()];
                    } else {
                        $arguments[$argument] = $attribute->hasDto()
                            ? $attribute->getDto()->build($request->getQueryParams())
                            : $request->getQueryParams();
                    }
                    break;
                }
                case Param::class: {
                    if ($attribute->hasName()) {
                        if (array_key_exists($argument, $params)) {
                            $arguments[$argument] = $params[$attribute->getName()];
                        } else {
                            throw new Exception("Parameter '{$argument}' is required.", 400);
                        }
                    } else {
                        $arguments[$argument] = $params;
                    }
                    break;
                }
                case Header::class: {
                    if ($attribute->hasName()) {
                        if ($request->hasHeader($attribute->getName())) {
                            $arguments[$argument] = $request->getHeader($attribute->getName());
                        } else {
                            if ($attribute->isRequired()) {
                                throw new Exception("Header '{$attribute->getName()}' is required.", 400);
                            }
                        }
                    } else {
                        $arguments[$argument] = $request->getHeaders();
                    }
                    break;
                }
                case Request::class: {
                    $arguments[$argument] = $request;
                    break;
                }
            }
        }
        return $arguments;
    }

    public function getClass(): string
    {
        if ($this->class === null) {
            throw new Exception('Class not set');
        }
        return $this->class;
    }

    public function getFunction(): string
    {
        if ($this->function === null) {
            throw new Exception('Function not set');
        }
        return $this->function;
    }

    public function getMethod(): Method
    {
        return $this->method;
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
