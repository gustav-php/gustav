<?php

namespace GustavPHP\Gustav\Session;

use GustavPHP\Gustav\Session\Exception\SessionStorageException;
use JsonException;

/** @internal */
final class FileSessionLease implements SessionLeaseInterface
{
    private bool $released = false;

    /** @param resource $handle */
    public function __construct(private $handle)
    {
    }

    public function __destruct()
    {
        $this->release();
    }

    public function destroy(): void
    {
        $this->writeBytes('');
    }

    public function read(): ?SessionRecord
    {
        $this->assertActive();
        if (rewind($this->handle) === false) {
            throw new SessionStorageException('Unable to read stored session');
        }
        $contents = stream_get_contents($this->handle);
        if ($contents === false) {
            throw new SessionStorageException('Unable to read stored session');
        }
        if ($contents === '') {
            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SessionStorageException('Stored session is invalid', previous: $exception);
        }
        if (
            !is_array($decoded)
            || !is_int($decoded['expires_at'] ?? null)
            || !is_array($decoded['data'] ?? null)
            || !is_array($decoded['flash'] ?? null)
        ) {
            throw new SessionStorageException('Stored session is invalid');
        }

        /** @var array<string,mixed> $data */
        $data = $decoded['data'];
        /** @var array<string,mixed> $flash */
        $flash = $decoded['flash'];

        return new SessionRecord($data, $flash, $decoded['expires_at']);
    }

    public function release(): void
    {
        if ($this->released) {
            return;
        }
        $this->released = true;
        @flock($this->handle, LOCK_UN);
        @fclose($this->handle);
    }

    public function write(SessionRecord $record): void
    {
        try {
            $contents = json_encode(
                [
                    'expires_at' => $record->expiresAt,
                    'data' => $record->data,
                    'flash' => $record->flash,
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new SessionStorageException('Session data could not be encoded', previous: $exception);
        }

        $this->writeBytes($contents);
    }

    private function assertActive(): void
    {
        if ($this->released) {
            throw new SessionStorageException('Session storage lease has already been released');
        }
    }

    private function writeBytes(string $contents): void
    {
        $this->assertActive();
        if (rewind($this->handle) === false || !ftruncate($this->handle, 0)) {
            throw new SessionStorageException('Unable to write stored session');
        }

        $length = strlen($contents);
        $written = 0;
        while ($written < $length) {
            $chunk = fwrite($this->handle, substr($contents, $written));
            if ($chunk === false || $chunk === 0) {
                throw new SessionStorageException('Unable to write stored session');
            }
            $written += $chunk;
        }
        if (!fflush($this->handle)) {
            throw new SessionStorageException('Unable to flush stored session');
        }
        if (function_exists('fsync') && !@fsync($this->handle)) {
            throw new SessionStorageException('Unable to synchronize stored session');
        }
    }
}
