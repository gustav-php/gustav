<?php

use GustavPHP\Gustav\Session\{FileSessionStore, SessionOptions, SessionRecord};

function temporarySessionDirectory(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gustav-session-test-' . bin2hex(random_bytes(8));
}

function removeSessionDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    foreach (new DirectoryIterator($directory) as $file) {
        if ($file->isFile()) {
            unlink($file->getPathname());
        }
    }
    rmdir($directory);
}

it('creates storage lazily and persists records between store instances', function () {
    $directory = temporarySessionDirectory();
    $options = new SessionOptions(directory: $directory, garbageCollectionProbability: 0);
    $id = str_repeat('a', 43);

    try {
        $store = new FileSessionStore($options);
        expect(is_dir($directory))->toBeFalse();
        $lease = $store->acquire($id, true);
        expect($lease)->not->toBeNull();
        $lease?->write(new SessionRecord(['count' => 0], ['notice' => 'saved'], time() + 60));
        $lease?->release();

        $restored = (new FileSessionStore($options))->acquire($id);
        expect($restored?->read()?->data)->toBe(['count' => 0])
            ->and($restored?->read()?->flash)->toBe(['notice' => 'saved']);
        $restored?->release();
    } finally {
        removeSessionDirectory($directory);
    }
});

it('does not create a file for an unrecognized client identifier', function () {
    $directory = temporarySessionDirectory();
    $store = new FileSessionStore(new SessionOptions(
        directory: $directory,
        garbageCollectionProbability: 0,
    ));

    try {
        expect($store->acquire(str_repeat('b', 43)))->toBeNull();
        $files = iterator_to_array(new FilesystemIterator($directory));
        expect($files)->toBe([]);
    } finally {
        removeSessionDirectory($directory);
    }
});

it('collects expired and empty records while keeping active sessions', function () {
    $directory = temporarySessionDirectory();
    $store = new FileSessionStore(new SessionOptions(
        directory: $directory,
        garbageCollectionProbability: 0,
    ));
    $expired = str_repeat('c', 43);
    $empty = str_repeat('d', 43);
    $active = str_repeat('e', 43);

    try {
        $lease = $store->acquire($expired, true);
        $lease?->write(new SessionRecord([], [], time() - 1));
        $lease?->release();
        $store->acquire($empty, true)?->release();
        $lease = $store->acquire($active, true);
        $lease?->write(new SessionRecord(['active' => true], [], time() + 60));
        $lease?->release();

        $store->collectGarbage();

        expect($store->acquire($expired))->toBeNull()
            ->and($store->acquire($empty))->toBeNull();
        $activeLease = $store->acquire($active);
        expect($activeLease?->read()?->data)->toBe(['active' => true]);
        $activeLease?->release();
    } finally {
        removeSessionDirectory($directory);
    }
});
