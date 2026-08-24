<?php

namespace GustavPHP\Gustav\Session;

use InvalidArgumentException;

final readonly class SessionOptions
{
    public function __construct(
        public ?string $directory = null,
        public string $cookieName = 'gustav_session',
        public int $lifetime = 7200,
        public string $cookiePath = '/',
        public ?string $cookieDomain = null,
        public ?bool $secure = null,
        public SameSite $sameSite = SameSite::Lax,
        public int $garbageCollectionProbability = 1,
    ) {
        if ($directory !== null && (trim($directory) === '' || str_contains($directory, "\0"))) {
            throw new InvalidArgumentException('Session directory must be a non-empty path');
        }
        if (preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]*$/D', $cookieName) !== 1) {
            throw new InvalidArgumentException('Session cookie name is invalid');
        }
        if ($lifetime < 1) {
            throw new InvalidArgumentException('Session lifetime must be greater than zero');
        }
        if (
            !str_starts_with($cookiePath, '/')
            || preg_match('/[;,\x00-\x1F\x7F]/', $cookiePath) === 1
        ) {
            throw new InvalidArgumentException('Session cookie path is invalid');
        }
        if (
            $cookieDomain !== null
            && (
                trim($cookieDomain) === ''
                || preg_match('/[;,\s\x00-\x1F\x7F]/', $cookieDomain) === 1
            )
        ) {
            throw new InvalidArgumentException('Session cookie domain is invalid');
        }
        if ($sameSite === SameSite::None && $secure !== true) {
            throw new InvalidArgumentException('SameSite=None session cookies must explicitly enable Secure');
        }
        if (str_starts_with($cookieName, '__Secure-') && $secure !== true) {
            throw new InvalidArgumentException('__Secure- session cookies must explicitly enable Secure');
        }
        if (
            str_starts_with($cookieName, '__Host-')
            && ($secure !== true || $cookieDomain !== null || $cookiePath !== '/')
        ) {
            throw new InvalidArgumentException(
                '__Host- session cookies require Secure, Path=/, and no Domain',
            );
        }
        if ($garbageCollectionProbability < 0 || $garbageCollectionProbability > 100) {
            throw new InvalidArgumentException(
                'Session garbage collection probability must be between 0 and 100',
            );
        }
    }
}
