<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class CookieResolver implements InputResolver
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
        return Source::Cookie;
    }

    public function resolve(BindingContext $context): Resolution
    {
        $cookies = $context->request->getCookieParams();
        if ($this->key === null) {
            return Resolution::present($cookies);
        }

        return array_key_exists($this->key, $cookies)
            ? Resolution::present($cookies[$this->key])
            : Resolution::missing();
    }
}
