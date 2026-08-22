<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestCircularB
{
    public function __construct(public ContainerTestCircularA $a)
    {
    }
}
