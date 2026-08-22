<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{
    Body,
    Cookie,
    Header,
    Param,
    Query,
    Request,
    Route,
    Validate
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;
use GustavPHP\Gustav\Validation\Common\{Decimal, Email};
use GustavPHP\Tests\Integration\DTO\{BodyInput, LegacyQueryInput, QueryInput};
use Psr\Http\Message\ServerRequestInterface;

class Params extends Controller\Base
{
    #[Route('/params/body', Method::POST)]
    public function body(
        #[Body] array $all,
        #[Body('required')] string $required,
        #[Body('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }

    #[Route('/params/body-dto', Method::POST)]
    public function bodyDto(
        #[Body] BodyInput $input,
        #[Request] ServerRequestInterface $request,
    ): Controller\Response {
        return $this->json([
            'email' => $input->email,
            'age' => $input->age,
            'active' => $input->active,
            'status' => $input->status->value,
            'nickname' => $input->nickname,
            'label' => $input->label,
            'stream_position' => $request->getBody()->tell(),
        ]);
    }
    #[Route('/params/cookie')]
    public function cookie(
        #[Cookie] array $all,
        #[Cookie('required')] string $required,
        #[Cookie('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }

    #[Route('/params/header')]
    public function header(
        #[Header] array $all,
        #[Header('required')] string $required,
        #[Header('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }

    #[Route('/params/legacy-query-dto')]
    public function legacyQueryDto(#[Query] LegacyQueryInput $input): Controller\Response
    {
        return $this->json(['term' => $input->term, 'page' => $input->page]);
    }

    #[Route('/params/manual-validation')]
    public function manualValidation(
        #[Query('email')] string $email,
        #[Query('score')] float $score,
    ): Controller\Response {
        $this->validate([
            [$email, new Email(), 'email'],
            [$score, new Decimal(min: -2, max: 2), 'score'],
        ]);

        return $this->json(['email' => $email, 'score' => $score]);
    }

    #[Route('/params/path/{required}')]
    public function param(
        #[Param('required')] string $required,
    ): Controller\Response {
        return $this->json([
            'required' => $required
        ]);
    }

    #[Route('/params/path-alias/{id}')]
    public function paramAlias(#[Param('id')] int $value): Controller\Response
    {
        return $this->json(['value' => $value]);
    }

    #[Route('/params/query')]
    public function query(
        #[Query] array $all,
        #[Query('required')] string $required,
        #[Query('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }

    #[Route('/params/query-dto')]
    public function queryDto(#[Query] QueryInput $input): Controller\Response
    {
        return $this->json([
            'term' => $input->term,
            'page' => $input->page,
            'archived' => $input->archived,
            'status' => $input->status->value,
        ]);
    }

    #[Route('/params/typed/{id}', Method::POST)]
    public function typed(
        #[Param('id')] int $id,
        #[Query('zero')] int $zero,
        #[Query('enabled')] bool $enabled,
        #[Header('X-Count')] int $count,
        #[Cookie('enabled')] bool $cookieEnabled,
        #[Body('nullable')] ?string $nullable,
        #[Body('optional')] int $optional = 7,
    ): Controller\Response {
        return $this->json([
            'id' => $id,
            'zero' => $zero,
            'enabled' => $enabled,
            'count' => $count,
            'cookie_enabled' => $cookieEnabled,
            'nullable' => $nullable,
            'optional' => $optional,
        ]);
    }

    #[Route('/params/validated')]
    public function validated(
        #[Query('email')]
        #[Validate(new Email())]
        string $email,
        #[Query('score')]
        #[Validate(new Decimal(min: -2, max: 2))]
        float $score,
    ): Controller\Response {
        return $this->json(['email' => $email, 'score' => $score]);
    }
}
