<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class RequestResolver implements InputResolver
{
    public function getPath(): string
    {
        return '';
    }

    public function getSource(): Source
    {
        return Source::Request;
    }

    public function resolve(BindingContext $context): Resolution
    {
        return Resolution::present($context->request);
    }
}
