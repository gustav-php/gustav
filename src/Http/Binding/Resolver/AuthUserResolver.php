<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Auth\Exception\UnauthorizedException;
use GustavPHP\Gustav\Auth\Identity;
use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

final readonly class AuthUserResolver implements InputResolver
{
    public function getPath(): string
    {
        return '';
    }

    public function getSource(): Source
    {
        return Source::AuthUser;
    }

    public function resolve(BindingContext $context): Resolution
    {
        $identity = $context->request->getAttribute(Identity::class)
            ?? $context->request->getAttribute('identity');
        if (!$identity instanceof Identity) {
            throw new UnauthorizedException('Authentication is required');
        }

        return Resolution::present($identity);
    }
}
