<?php

namespace GustavPHP\Gustav\Service;

/** @internal */
final readonly class Registration
{
    /**
     * @param class-string $service
     * @param class-string $implementation
     */
    public function __construct(
        public string $service,
        public string $implementation,
        public Lifetime $lifetime,
    ) {
    }
}
