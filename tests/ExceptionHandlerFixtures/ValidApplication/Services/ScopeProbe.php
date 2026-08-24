<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Services;

use WeakReference;

final class ScopeProbe
{
    public readonly int $id;
    private static ?WeakReference $last = null;

    private static int $next = 0;

    public function __construct()
    {
        $this->id = ++self::$next;
        self::$last = WeakReference::create($this);
    }

    public static function alive(): bool
    {
        return self::$last?->get() instanceof self;
    }

    public static function instances(): int
    {
        return self::$next;
    }

    public static function reset(): void
    {
        self::$last = null;
        self::$next = 0;
    }
}
