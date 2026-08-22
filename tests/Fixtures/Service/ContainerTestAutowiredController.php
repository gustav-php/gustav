<?php

namespace GustavPHP\Tests\Fixtures\Service;

use GustavPHP\Gustav\Controller\Base;

class ContainerTestAutowiredController extends Base
{
    public function __construct(public ContainerTestNestedDependency $dependency)
    {
    }
}
