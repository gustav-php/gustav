<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
abstract class AbstractHandler
{
    abstract public function __invoke(DomainFailure $exception): Response;
}
