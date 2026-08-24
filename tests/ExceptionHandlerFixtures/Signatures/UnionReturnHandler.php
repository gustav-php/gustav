<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;
use Psr\Http\Message\ResponseInterface;

#[ExceptionHandler]
final readonly class UnionReturnHandler
{
    public function __invoke(DomainFailure $exception): Response|ResponseInterface
    {
        return new Response(body: $exception->getMessage());
    }
}
