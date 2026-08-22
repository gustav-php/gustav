<?php

namespace GustavPHP\Tests\Fixtures\Service;

use GustavPHP\Gustav\Controller\Base;

class ContainerTestDefinitionController extends Base
{
    public function __construct(public ContainerTestProvidedService $provided)
    {
    }
}
