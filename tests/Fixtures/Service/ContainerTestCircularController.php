<?php

namespace GustavPHP\Tests\Fixtures\Service;

use GustavPHP\Gustav\Controller\Base;

class ContainerTestCircularController extends Base
{
    public function __construct(public ContainerTestCircularA $a)
    {
    }
}
