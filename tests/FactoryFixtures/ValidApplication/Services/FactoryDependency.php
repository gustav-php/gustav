<?php

namespace GustavPHP\Tests\FactoryFixtures\ValidApplication\Services;

final readonly class FactoryDependency
{
    public string $value;

    public function __construct()
    {
        $this->value = 'dependency';
    }
}
