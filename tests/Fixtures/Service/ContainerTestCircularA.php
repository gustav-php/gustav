<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestCircularA
{
    public function __construct(public ContainerTestCircularB $b)
    {
    }
}
