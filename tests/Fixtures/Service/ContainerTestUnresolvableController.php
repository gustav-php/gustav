<?php

namespace GustavPHP\Tests\Fixtures\Service;

use GustavPHP\Gustav\Controller\Base;

class ContainerTestUnresolvableController extends Base
{
    public function __construct(public string $value)
    {
    }
}
