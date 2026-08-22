<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestInvokableService
{
    public function __invoke(): string
    {
        return 'invoked';
    }
}
