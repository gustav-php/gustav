<?php

namespace GustavPHP\Tests\Integration\Services;

class Nested extends \GustavPHP\Gustav\Service\Base
{
    public function __construct(protected Simple $simple)
    {
    }

    public function getTestValue(): string
    {
        return $this->simple->getTestValue();
    }
}
