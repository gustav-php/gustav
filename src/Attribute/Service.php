<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Service\Lifetime;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Service
{
    /**
     * @param null|class-string $as
     */
    public function __construct(
        private ?string $as = null,
        private Lifetime $lifetime = Lifetime::Scoped,
    ) {
        if (
            $as !== null
            && !class_exists($as)
            && !interface_exists($as)
        ) {
            throw new InvalidArgumentException("Service abstraction '{$as}' does not exist");
        }
    }

    public function getLifetime(): Lifetime
    {
        return $this->lifetime;
    }

    /** @return null|class-string */
    public function getService(): ?string
    {
        return $this->as;
    }
}
