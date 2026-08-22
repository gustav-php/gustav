<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class ParamResolver implements InputResolver
{
    public function __construct(private ?string $name)
    {
    }

    public function getPath(): string
    {
        return $this->name ?? '';
    }

    public function getSource(): Source
    {
        return Source::Param;
    }

    public function resolve(BindingContext $context): Resolution
    {
        $params = $context->params();
        if ($this->name === null) {
            return Resolution::present($params);
        }

        return array_key_exists($this->name, $params)
            ? Resolution::present($params[$this->name])
            : Resolution::missing();
    }
}
