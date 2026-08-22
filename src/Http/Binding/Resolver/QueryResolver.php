<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class QueryResolver implements InputResolver
{
    public function __construct(private ?string $key)
    {
    }

    public function getPath(): string
    {
        return $this->key ?? '';
    }

    public function getSource(): Source
    {
        return Source::Query;
    }

    public function resolve(BindingContext $context): Resolution
    {
        $query = $context->request->getQueryParams();
        if ($this->key === null) {
            return Resolution::present($query);
        }

        return array_key_exists($this->key, $query)
            ? Resolution::present($query[$this->key])
            : Resolution::missing();
    }
}
