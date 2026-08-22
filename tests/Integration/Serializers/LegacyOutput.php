<?php

namespace GustavPHP\Tests\Integration\Serializers;

use AllowDynamicProperties;
use GustavPHP\Gustav\Attribute\Serializer\{AdditionalProperties, Exclude};
use GustavPHP\Gustav\Serializer;

#[AllowDynamicProperties]
#[AdditionalProperties]
final class LegacyOutput extends Serializer\Base
{
    /** @var list<LegacyChild|string|int> */
    public array $items;
    public string $name = 'legacy';
    #[Exclude]
    public string $secret = 'internal';

    public function __construct()
    {
        $this->items = [new LegacyChild(), 'kept', 0];
    }
}
