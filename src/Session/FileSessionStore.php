<?php

namespace GustavPHP\Gustav\Session;

use DirectoryIterator;
use GustavPHP\Gustav\Session\Exception\SessionStorageException;
use InvalidArgumentException;

final class FileSessionStore implements SessionStoreInterface
{
    private ?string $resolvedDirectory = null;

    public function __construct(private readonly SessionOptions $options)
    {
    }

    public function acquire(string $id, bool $create = false): ?SessionLeaseInterface
    {
        $this->assertId($id);
        $directory = $this->directory();
        $this->maybeCollectGarbage();
        $filename = $directory . DIRECTORY_SEPARATOR . hash('sha256', $id) . '.session';
        if (!$create && !is_file($filename)) {
            return null;
        }

        $handle = @fopen($filename, $create ? 'c+b' : 'r+b');
        if ($handle === false) {
            if (!$create && !is_file($filename)) {
                return null;
            }

            throw new SessionStorageException('Unable to open session storage');
        }
        @chmod($filename, 0600);
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new SessionStorageException('Unable to lock session storage');
        }

        return new FileSessionLease($handle);
    }

    public function collectGarbage(?int $now = null): void
    {
        $now ??= time();
        foreach (new DirectoryIterator($this->directory()) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'session') {
                continue;
            }
            $handle = @fopen($file->getPathname(), 'r+b');
            if ($handle === false || !flock($handle, LOCK_EX | LOCK_NB)) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                continue;
            }

            $remove = false;
            $lease = new FileSessionLease($handle);
            try {
                $record = $lease->read();
                $remove = $record === null || $record->expiresAt <= $now;
            } catch (SessionStorageException) {
                $remove = true;
            }
            if ($remove) {
                $lease->destroy();
            }
            $pathname = $file->getPathname();
            $lease->release();
            if ($remove) {
                @unlink($pathname);
            }
        }
    }

    private function assertId(string $id): void
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $id) !== 1) {
            throw new InvalidArgumentException('Session id is invalid');
        }
    }

    private function directory(): string
    {
        if ($this->resolvedDirectory !== null) {
            return $this->resolvedDirectory;
        }
        $directory = $this->options->directory;
        if ($directory === null) {
            throw new SessionStorageException(
                'The default session store requires a configured directory',
            );
        }
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new SessionStorageException("Session directory '{$directory}' could not be created");
        }
        $resolved = realpath($directory);
        if ($resolved === false || !is_dir($resolved) || !is_readable($resolved) || !is_writable($resolved)) {
            throw new SessionStorageException("Session directory '{$directory}' is not readable and writable");
        }

        return $this->resolvedDirectory = $resolved;
    }

    private function maybeCollectGarbage(): void
    {
        $probability = $this->options->garbageCollectionProbability;
        if ($probability > 0 && random_int(1, 100) <= $probability) {
            $this->collectGarbage();
        }
    }
}
