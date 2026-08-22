<?php

namespace GustavPHP\Tests\Integration\Serializers;

use GustavPHP\Gustav\Serializer;

final class LegacyChild extends Serializer\Base
{
    public string $name = 'child';
}
