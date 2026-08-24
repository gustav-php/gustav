<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\Signatures;

use GustavPHP\Gustav\Attribute\ExceptionHandler;
use GustavPHP\Gustav\Controller\Response;

#[ExceptionHandler]
final readonly class VariadicParameterHandler
{
    public function __invoke(DomainFailure ...$exceptions): Response
    {
        return new Response(body: count($exceptions));
    }
}
