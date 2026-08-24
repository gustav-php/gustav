<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Service\Lifetime;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Factory
{
    public function __construct(private Lifetime $lifetime = Lifetime::Scoped)
    {
    }

    public function getLifetime(): Lifetime
    {
        return $this->lifetime;
    }
}
