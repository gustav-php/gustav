<?php

namespace GustavPHP\Tests\Fixtures\Service;

use Psr\Http\Message\ServerRequestInterface;

class ContainerTestScopedConsumer
{
    public function __construct(
        public ContainerTestRequestService $service,
        public ServerRequestInterface $request,
    ) {
    }
}
