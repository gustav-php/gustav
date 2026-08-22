<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestNestedDependency
{
    public function __construct(public ContainerTestPlainDependency $plain)
    {
    }
}
