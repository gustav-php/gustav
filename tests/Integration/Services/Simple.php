<?php

namespace GustavPHP\Tests\Integration\Services;

class Simple extends \GustavPHP\Gustav\Service\Base
{
    public const TEST_STRING = 'Test string';

    public function getTestValue(): string
    {
        return self::TEST_STRING;
    }
}
