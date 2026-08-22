<?php

namespace GustavPHP\Tests\Fixtures\Service;

class ContainerTestInvalidSingleton
{
    public function __construct(public ContainerTestRequestService $request)
    {
    }
}
