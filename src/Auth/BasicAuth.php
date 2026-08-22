<?php

namespace GustavPHP\Gustav\Auth;

use GustavPHP\Gustav\Auth\Exception\{InvalidAuthHeaderException, InvalidBasicCredentialsException, MissingAuthHeaderException};
use Psr\Http\Message\ServerRequestInterface;

readonly class BasicAuth
{
    private const CHALLENGE = 'Basic realm="Restricted"';

    public function __construct(
        private string $username,
        private string $password
    ) {
    }

    /**
     * Extract Basic Auth credentials from request.
     *
     * @param ServerRequestInterface $request
     * @return self
     * @throws MissingAuthHeaderException
     * @throws InvalidAuthHeaderException
     * @throws InvalidBasicCredentialsException
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '') {
            throw new MissingAuthHeaderException(
                'Authorization header is required for basic authentication',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        if (!preg_match('/^Basic(?:[ \t]+(.*))?$/iD', $authHeader, $matches)) {
            throw new InvalidAuthHeaderException(
                'Authorization header must contain Basic credentials',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        $credentials = $matches[1] ?? '';
        if ($credentials === '' || preg_match('/\s/', $credentials)) {
            throw new InvalidBasicCredentialsException(
                'Basic credentials are empty or malformed',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        $decoded = base64_decode($credentials, true);

        if ($decoded === false) {
            throw new InvalidBasicCredentialsException(
                'Basic credentials are not valid base64',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        $parts = explode(':', $decoded, 2);

        if (count($parts) !== 2) {
            throw new InvalidBasicCredentialsException(
                'Basic credentials must be in format "username:password"',
                ['WWW-Authenticate' => self::CHALLENGE],
            );
        }

        return new self($parts[0], $parts[1]);
    }

    /**
     * Get the password.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the username.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }
}
