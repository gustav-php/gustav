<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\ExceptionHandlers;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\HandlerFailure;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Services\ScopeProbe;
use RuntimeException;

#[ExceptionHandler]
final readonly class FailingHandler
{
    public function __construct(private ScopeProbe $scope)
    {
    }

    public function __invoke(HandlerFailure $exception): Response
    {
        throw new RuntimeException(
            'handler secret ' . $this->scope->id . ' after ' . $exception->getMessage(),
        );
    }
}
