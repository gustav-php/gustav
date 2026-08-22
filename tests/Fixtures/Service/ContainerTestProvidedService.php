<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestProvidedService
{
    public function __construct(public string $value)
    {
    }
}
