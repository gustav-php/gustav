<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestImplementation implements ContainerTestContract
{
    public function value(): string
    {
        return 'implementation';
    }
}
