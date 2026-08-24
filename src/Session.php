<?php

namespace GustavPHP\Gustav;

use GustavPHP\Gustav\Session\Exception\SessionStorageException;
use GustavPHP\Gustav\Session\{SessionLeaseInterface, SessionOptions, SessionRecord, SessionStoreInterface};
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Throwable;

final class Session
{
    private bool $clearCookie = false;

    private bool $closed = false;

    /** @var array<string,mixed> */
    private array $data = [];

    private ?string $id = null;

    private ?SessionLeaseInterface $lease = null;

    private bool $loaded = false;

    /** @var array<string,mixed> */
    private array $newFlash = [];

    /** @var array<string,mixed> */
    private array $oldFlash = [];

    public function __construct(
        private readonly SessionStoreInterface $store,
        private readonly SessionOptions $options,
        private readonly ServerRequestInterface $request,
    ) {
        $cookie = $request->getCookieParams()[$options->cookieName] ?? null;
        if ($cookie === null) {
            return;
        }
        if (!is_string($cookie) || !$this->validId($cookie)) {
            $this->clearCookie = true;

            return;
        }

        $this->id = $cookie;
    }

    public function __destruct()
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->releaseLease();
        } catch (Throwable) {
        }
    }

    /** @internal */
    public function abort(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->releaseLease();
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        $this->load();

        return $this->data;
    }

    /** @return array<string,mixed> */
    public function allFlash(): array
    {
        $this->load();

        return array_merge($this->oldFlash, $this->newFlash);
    }

    public function clear(): void
    {
        $this->load();
        if ($this->data === []) {
            return;
        }
        $this->ensureSession();
        $this->data = [];
    }

    /** @internal */
    public function complete(ResponseInterface $response): ResponseInterface
    {
        $this->assertOpen();
        $this->closed = true;

        try {
            if ($response->getStatusCode() >= 500) {
                return $response;
            }
            if ($this->id !== null && $this->lease !== null) {
                $this->lease->write(new SessionRecord(
                    data: $this->data,
                    flash: $this->newFlash,
                    expiresAt: time() + $this->options->lifetime,
                ));

                return $response->withAddedHeader('Set-Cookie', $this->sessionCookie());
            }
            if ($this->clearCookie) {
                return $response->withAddedHeader('Set-Cookie', $this->expiredCookie());
            }

            return $response;
        } finally {
            $this->releaseLease();
        }
    }

    public function flash(string $key, mixed $value): void
    {
        $this->assertKey($key);
        $this->assertValue($value);
        $this->load();
        $this->ensureSession();
        $this->newFlash[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertKey($key);
        $this->load();

        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : $default;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->assertKey($key);
        $this->load();
        if (array_key_exists($key, $this->newFlash)) {
            return $this->newFlash[$key];
        }

        return array_key_exists($key, $this->oldFlash)
            ? $this->oldFlash[$key]
            : $default;
    }

    public function has(string $key): bool
    {
        $this->assertKey($key);
        $this->load();

        return array_key_exists($key, $this->data);
    }

    public function hasFlash(string $key): bool
    {
        $this->assertKey($key);
        $this->load();

        return array_key_exists($key, $this->newFlash)
            || array_key_exists($key, $this->oldFlash);
    }

    public function id(): string
    {
        $this->load();
        $this->ensureSession();

        return $this->id ?? throw new LogicException('Session id was not created');
    }

    public function invalidate(): void
    {
        $this->load();
        if ($this->lease !== null) {
            $this->lease->destroy();
        }
        $this->releaseLease();
        $this->id = null;
        $this->data = [];
        $this->oldFlash = [];
        $this->newFlash = [];
        $this->clearCookie = true;
    }

    public function keepFlash(string ...$keys): void
    {
        $this->load();
        if ($keys === []) {
            $keys = array_keys($this->oldFlash);
        }
        foreach ($keys as $key) {
            $this->assertKey($key);
            if (array_key_exists($key, $this->oldFlash)) {
                $this->newFlash[$key] = $this->oldFlash[$key];
            }
        }
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->getFlash($key, $default);
        unset($this->oldFlash[$key], $this->newFlash[$key]);

        return $value;
    }

    public function put(string $key, mixed $value): void
    {
        $this->assertKey($key);
        $this->assertValue($value);
        $this->load();
        $this->ensureSession();
        $this->data[$key] = $value;
    }

    public function regenerate(): void
    {
        $this->load();
        if ($this->lease !== null) {
            $this->lease->destroy();
        }
        $this->releaseLease();
        $this->id = null;
        $this->clearCookie = true;
        $this->ensureSession();
    }

    public function remove(string $key): mixed
    {
        $this->assertKey($key);
        $this->load();
        if (!array_key_exists($key, $this->data)) {
            return null;
        }
        $value = $this->data[$key];
        unset($this->data[$key]);

        return $value;
    }

    private function assertKey(string $key): void
    {
        $this->assertOpen();
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $key) !== 1) {
            throw new InvalidArgumentException('Session keys must be valid names');
        }
    }

    private function assertOpen(): void
    {
        if ($this->closed) {
            throw new LogicException('Session is no longer active');
        }
    }

    private function assertRecord(SessionRecord $record): void
    {
        foreach ($record->data as $key => $value) {
            if (!is_string($key)) {
                throw new SessionStorageException('Stored session is invalid');
            }
            $this->assertStoredKeyAndValue($key, $value);
        }
        foreach ($record->flash as $key => $value) {
            if (!is_string($key)) {
                throw new SessionStorageException('Stored session is invalid');
            }
            $this->assertStoredKeyAndValue($key, $value);
        }
    }

    private function assertStoredKeyAndValue(string $key, mixed $value): void
    {
        try {
            $this->assertKey($key);
            $this->assertValue($value);
        } catch (InvalidArgumentException $exception) {
            throw new SessionStorageException('Stored session is invalid', previous: $exception);
        }
    }

    private function assertValue(mixed $value, int $depth = 0): void
    {
        if ($depth > 64) {
            throw new InvalidArgumentException('Session values cannot exceed 64 levels');
        }
        if ($value === null || is_bool($value) || is_int($value)) {
            return;
        }
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException('Session floats must be finite');
            }

            return;
        }
        if (is_string($value)) {
            if (preg_match('//u', $value) !== 1) {
                throw new InvalidArgumentException('Session strings must contain valid UTF-8');
            }

            return;
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('//u', $key) !== 1) {
                    throw new InvalidArgumentException('Session array keys must contain valid UTF-8');
                }
                $this->assertValue($item, $depth + 1);
            }

            return;
        }

        throw new InvalidArgumentException(
            'Session values must contain only JSON-compatible scalars and arrays',
        );
    }

    /** @return list<string> */
    private function cookieAttributes(int $expiresAt, int $maxAge): array
    {
        $attributes = [
            'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', $expiresAt),
            "Max-Age={$maxAge}",
            "Path={$this->options->cookiePath}",
        ];
        if ($this->options->cookieDomain !== null) {
            $attributes[] = "Domain={$this->options->cookieDomain}";
        }
        if ($this->secureCookie()) {
            $attributes[] = 'Secure';
        }
        $attributes[] = 'HttpOnly';
        $attributes[] = 'SameSite=' . $this->options->sameSite->value;

        return $attributes;
    }

    private function ensureSession(): void
    {
        if ($this->id !== null && $this->lease !== null) {
            return;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $id = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $lease = $this->store->acquire($id, true);
            if ($lease === null) {
                continue;
            }
            try {
                if ($lease->read() !== null) {
                    $lease->release();
                    continue;
                }
            } catch (Throwable $exception) {
                $lease->release();
                throw $exception;
            }

            $this->id = $id;
            $this->lease = $lease;
            $this->clearCookie = false;

            return;
        }

        throw new SessionStorageException('Unable to allocate a unique session id');
    }

    private function expiredCookie(): string
    {
        return implode('; ', [
            $this->options->cookieName . '=',
            ...$this->cookieAttributes(0, 0),
        ]);
    }

    private function load(): void
    {
        $this->assertOpen();
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        if ($this->id === null) {
            return;
        }

        $lease = $this->store->acquire($this->id);
        if ($lease === null) {
            $this->id = null;
            $this->clearCookie = true;

            return;
        }
        $this->lease = $lease;
        try {
            $record = $lease->read();
            if ($record === null || $record->expiresAt <= time()) {
                $lease->destroy();
                $this->releaseLease();
                $this->id = null;
                $this->clearCookie = true;

                return;
            }
            $this->assertRecord($record);
            $this->data = $record->data;
            $this->oldFlash = $record->flash;
        } catch (Throwable $exception) {
            $this->releaseLease();
            throw $exception;
        }
    }

    private function releaseLease(): void
    {
        $lease = $this->lease;
        $this->lease = null;
        $lease?->release();
    }

    private function secureCookie(): bool
    {
        return $this->options->secure
            ?? strtolower($this->request->getUri()->getScheme()) === 'https';
    }

    private function sessionCookie(): string
    {
        $id = $this->id ?? throw new LogicException('Session id is missing');

        return implode('; ', [
            $this->options->cookieName . '=' . $id,
            ...$this->cookieAttributes(time() + $this->options->lifetime, $this->options->lifetime),
        ]);
    }

    private function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{43}$/D', $id) === 1;
    }
}
