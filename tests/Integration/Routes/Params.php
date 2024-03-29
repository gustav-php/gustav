<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{
    Body,
    Cookie,
    Header,
    Param,
    Query,
    Route
};
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;

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

    #[Route('/params/path/{required}')]
    public function param(
        #[Param('required')] string $required,
    ): Controller\Response {
        return $this->json([
            'required' => $required
        ]);
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
}
