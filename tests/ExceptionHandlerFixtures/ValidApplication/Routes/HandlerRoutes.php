<?php

namespace GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get, Query};
use GustavPHP\Gustav\Controller\Response;
use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Tests\ExceptionHandlerFixtures\ValidApplication\Exceptions\{
    ChildSpecificFailure,
    ClientFailure,
    DomainFailure,
    HandlerFailure,
    PsrFailure,
    ScopedFailure,
    ServerFailure,
    SpecificFailure,
    UnmatchedFailure,
    ViewFailure
};

#[Controller('/handlers')]
final readonly class HandlerRoutes
{
    #[Get('/chained-handler-failure')]
    public function chainedHandlerFailure(): Response
    {
        throw new ServerFailure('first server secret');
    }
    #[Get('/child')]
    public function child(): Response
    {
        throw new ChildSpecificFailure('child failure');
    }

    #[Get('/client')]
    public function client(): Response
    {
        throw new ClientFailure('Client mapping');
    }

    #[Get('/domain')]
    public function domain(): Response
    {
        throw new DomainFailure('domain failure');
    }

    #[Get('/handler-failure')]
    public function handlerFailure(): Response
    {
        throw new HandlerFailure('domain secret');
    }

    #[Get('/http')]
    public function http(): Response
    {
        throw new HttpException(418, 'Built-in HTTP mapping', ['X-Built-In' => 'yes']);
    }

    /** @return array{required: string} */
    #[Get('/input')]
    public function input(#[Query('required')] string $required): array
    {
        return ['required' => $required];
    }

    /** @return array{ok: true} */
    #[Get('/ok')]
    public function ok(): array
    {
        return ['ok' => true];
    }

    /** @return array{ok: true} */
    #[Get('/outer')]
    public function outer(): array
    {
        return ['ok' => true];
    }

    #[Get('/psr')]
    public function psr(): Response
    {
        throw new PsrFailure('accepted failure');
    }

    #[Get('/scoped')]
    public function scoped(): Response
    {
        throw new ScopedFailure('scoped failure');
    }

    #[Get('/server')]
    public function server(): Response
    {
        throw new ServerFailure('server source secret');
    }

    #[Get('/specific')]
    public function specific(): Response
    {
        throw new SpecificFailure('specific failure');
    }

    #[Get('/unmatched')]
    public function unmatched(): Response
    {
        throw new UnmatchedFailure('unmatched secret');
    }

    #[Get('/view')]
    public function view(): Response
    {
        throw new ViewFailure('gone & <strong>hidden</strong>');
    }
}
