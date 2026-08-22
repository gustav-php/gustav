<?php

namespace GustavPHP\Gustav\Service;

/** @internal */
final readonly class Definition
{
    public function __construct(
        public Lifetime $lifetime,
        public mixed $resolver,
    ) {
    }
}
