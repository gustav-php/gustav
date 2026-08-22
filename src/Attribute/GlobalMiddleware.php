<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Service\Lifetime;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class GlobalMiddleware
{
    public function __construct(
        private int $priority = 0,
        private Lifetime $lifetime = Lifetime::Request,
    ) {
    }

    public function getLifetime(): Lifetime
    {
        return $this->lifetime;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
