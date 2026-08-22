<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class HeaderResolver implements InputResolver
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
        return Source::Header;
    }

    public function resolve(BindingContext $context): Resolution
    {
        if ($this->name === null) {
            return Resolution::present($context->request->getHeaders());
        }

        return $context->request->hasHeader($this->name)
            ? Resolution::present($context->request->getHeaderLine($this->name))
            : Resolution::missing();
    }
}
