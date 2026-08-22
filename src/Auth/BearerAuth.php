<?php

namespace GustavPHP\Gustav\Auth;

use GustavPHP\Gustav\Auth\Exception\{InvalidAuthHeaderException, InvalidBearerTokenException, MissingAuthHeaderException};
use Psr\Http\Message\ServerRequestInterface;

readonly class BearerAuth
{
    private const CHALLENGE = 'Bearer';

    public function __construct(
        private string $token
    ) {
    }

    /**
     * Extract Bearer token from request.
     *
     * @param ServerRequestInterface $request
     * @return self
     * @throws MissingAuthHeaderException
     * @throws InvalidAuthHeaderException
     * @throws InvalidBearerTokenException
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '') {
            throw new MissingAuthHeaderException(
                'Authorization header is required for bearer authentication',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        if (!preg_match('/^Bearer(?:[ \t]+(.*))?$/iD', $authHeader, $matches)) {
            throw new InvalidAuthHeaderException(
                'Authorization header must contain a Bearer token',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        $token = $matches[1] ?? '';
        if ($token === '' || preg_match('/\s/', $token)) {
            throw new InvalidBearerTokenException(
                'Bearer token is empty or malformed',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        return new self($token);
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
