<?php

namespace GustavPHP\Tests\Integration\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Auth\{Authenticator, BearerAuth, Identity};
use GustavPHP\Gustav\Auth\Exception\UnauthorizedException;
use GustavPHP\Tests\Integration\Auth\TestIdentity;
use Psr\Http\Message\ServerRequestInterface;

#[Service(as: Authenticator::class)]
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
