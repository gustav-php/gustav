<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class BodyResolver implements InputResolver
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
        return Source::Body;
    }

    public function resolve(BindingContext $context): Resolution
    {
        $body = $context->body();
        if ($this->key === null) {
            return Resolution::present($body);
        }

        return array_key_exists($this->key, $body)
            ? Resolution::present($body[$this->key])
            : Resolution::missing();
    }
}
