<?php

namespace GustavPHP\Tests\Integration\Auth;

use GustavPHP\Gustav\Auth\{Authenticator, BearerAuth, Identity};
use GustavPHP\Gustav\Auth\Exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;

class HeaderAuthenticator implements Authenticator
{
    public function authenticate(ServerRequestInterface $request): Identity
    {
        $credentials = BearerAuth::fromRequest($request);
        if (!hash_equals('valid-token', $credentials->getToken())) {
            throw new UnauthorizedException(
                'Bearer token is invalid',
                ['WWW-Authenticate' => 'Bearer error="invalid_token"'],
            );
        }

        return new TestIdentity('user-123', ['reader']);
    }
}
