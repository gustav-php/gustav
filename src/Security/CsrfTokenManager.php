<?php

namespace GustavPHP\Gustav\Security;

use GustavPHP\Gustav\Session;

final readonly class CsrfTokenManager
{
    public const FIELD = '_token';

    public const HEADER = 'X-CSRF-Token';

    private const SESSION_KEY = '_csrf_token';

    public function __construct(private Session $session)
    {
    }

    public function isValid(mixed $submitted): bool
    {
        if (!is_string($submitted) || preg_match('/^[A-Za-z0-9_-]{43}$/D', $submitted) !== 1) {
            return false;
        }
        $stored = $this->session->get(self::SESSION_KEY);

        return is_string($stored) && hash_equals($stored, $submitted);
    }

    public function rotate(): string
    {
        $token = $this->newToken();
        $this->session->put(self::SESSION_KEY, $token);

        return $token;
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (is_string($token) && preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) === 1) {
            return $token;
        }

        return $this->rotate();
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
